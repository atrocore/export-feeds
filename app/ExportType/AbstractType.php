<?php
/*
 * Export Feeds
 * Free Extension
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Export\ExportType;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;
use Espo\Entities\Attachment;
use Espo\ORM\EntityManager;
use Espo\Core\Container;
use Espo\Services\Record;
use Export\DataConvertor\Base;
use Export\Entities\ExportJob;
use Treo\Core\FilePathBuilder;

abstract class AbstractType
{
    protected Container $container;

    protected array $data;

    private array $services = [];

    private array $languages = [];

    private array $convertors = [];

    public function __construct(Container $container, array $data)
    {
        $this->container = $container;
        $this->data = $data;
    }

    public static function getAllFieldsConfiguration(string $scope, Metadata $metadata, Language $language): array
    {
        $configuration = [['field' => 'id', 'column' => 'ID']];

        /** @var array $allFields */
        $allFields = $metadata->get(['entityDefs', $scope, 'fields'], []);

        foreach ($allFields as $field => $data) {
            if (!empty($data['exportDisabled']) || !empty($data['disabled'])
                || in_array(
                    $data['type'], ['jsonObject', 'linkParent', 'currencyConverted', 'available-currency', 'file', 'attachmentMultiple']
                )) {
                continue 1;
            }

            $row = [
                'field'  => $field,
                'column' => $language->translate($field, 'fields', $scope)
            ];

            if (isset($configuration[$row['column']])) {
                continue 1;
            }

            if (in_array($data['type'], ['link', 'linkMultiple'])) {
                $row['exportBy'] = ['id'];
            }

            if ($data['type'] === 'linkMultiple') {
                $row['exportIntoSeparateColumns'] = false;
                if ($scope === 'Product' && $field === 'productAttributeValues') {
                    $row['column'] = '...';
                    $row['exportIntoSeparateColumns'] = true;
                    $row['exportBy'] = ['value'];
                }
            }

            $configuration[$row['column']] = $row;

            // push locales fields
            if (!empty($data['isMultilang'])) {
                foreach ($allFields as $langField => $langData) {
                    if (!empty($langData['multilangField']) && $langData['multilangField'] == $field) {
                        $langRow = [
                            'field'  => $langField,
                            'column' => $language->translate($langField, 'fields', $scope)
                        ];
                        $configuration[$langRow['column']] = $langRow;
                    }
                }
            }
        }

        return array_values($configuration);
    }

    abstract public function export(ExportJob $exportJob): Attachment;

    protected function getDataConvertor(string $scope): Base
    {
        if (!isset($this->convertors[$scope])) {
            $className = "Export\\DataConvertor\\" . $scope;

            if (!class_exists($className)) {
                $className = Base::class;
            }

            if (!is_a($className, Base::class, true)) {
                throw new Error($className . ' should be instance of ' . Base::class);
            }

            $this->convertors[$scope] = new $className($this->container);
        }

        return $this->convertors[$scope];
    }

    protected function createCacheFile(ExportJob $exportJob): string
    {
        // prepare export feed data
        $data = $this->getFeedData();

        $configuration = $data['configuration'];

        if (!empty($this->data['exportByChannelId'])) {
            $channel = $this->getEntityManager()->getEntity('Channel', $this->data['exportByChannelId']);
            if (empty($channel)) {
                throw new BadRequest('No such channel found.');
            }
            $this->data['channelLocales'] = $channel->get('locales');
        }

        if (empty($records = $this->getRecords())) {
            throw new BadRequest($this->translate('noDataFound', 'exceptions', 'ExportFeed'));
        }

        $convertor = $this->getDataConvertor($data['entity']);

        // prepare full file name
        $fileName = "{$this->data['exportJobId']}.txt";
        $filePath = $this->createPath();
        $fullFilePath = $this->getConfig()->get('filesPath', 'upload/files/') . $filePath;
        Util::createDir($fullFilePath);

        $fullFileName = $fullFilePath . '/' . $fileName;

        // clearing file if it needs
        file_put_contents($fullFileName, '');

        $file = fopen($fullFileName, 'a');

        $columns = [];
        foreach ($records as $k => $record) {
            $pushRow = [];
            foreach ($configuration as $rowNumber => $row) {
                $row = $this->prepareRow($row);

                if (!empty($row['channelLocales']) && !empty($row['locale']) && !in_array($row['locale'], $row['channelLocales'])) {
                    continue 1;
                }

                $converted = $convertor->convert($record, $row);

                $n = 0;
                foreach ($converted as $colName => $value) {
                    $columns[$rowNumber . '_' . $colName] = [
                        'number' => $rowNumber,
                        'pos'    => $rowNumber * 1000 + $n,
                        'name'   => $colName,
                        'label'  => $convertor->getColumnLabel($colName, $this->data, $rowNumber)
                    ];
                    $n++;
                }

                $pushRow[] = $converted;
            }

            $pushContent = Json::encode($pushRow);
            if (isset($records[$k + 1])) {
                $pushContent .= PHP_EOL;
            }

            fwrite($file, $pushContent);
        }
        fclose($file);

        // sorting columns
        $sortedColumns = [];
        $number = 0;
        while (count($columns) > 0) {
            foreach ($columns as $k => $row) {
                if ($row['number'] == $number) {
                    $sortedColumns[] = $row;
                    unset($columns[$k]);
                }
            }
            $number++;
        }

        $exportJob->set('data', array_merge($exportJob->getData(), ['columns' => $sortedColumns, 'fullFileName' => $fullFileName]));

        return $fullFileName;
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->container->get('entityManager');
    }

    protected function getService(string $serviceName): Record
    {
        if (!isset($this->services[$serviceName])) {
            $this->services[$serviceName] = $this->container->get('serviceFactory')->create($serviceName);
        }

        return $this->services[$serviceName];
    }

    protected function getConfig(): Config
    {
        return $this->container->get('config');
    }

    protected function getMetadata(): Metadata
    {
        return $this->container->get('metadata');
    }

    protected function translate(string $key, string $tab, string $scope = 'Global'): string
    {
        return $this->container->get('language')->translate($key, $tab, $scope);
    }

    protected function getSelectManager(string $name): \Espo\Core\SelectManagers\Base
    {
        return $this->container->get('selectManagerFactory')->create($name);
    }

    protected function getLanguage(string $locale): Language
    {
        if (!isset($this->languages[$locale])) {
            $this->languages[$locale] = new Language($this->container, $locale);
        }

        return $this->languages[$locale];
    }

    protected function createPath(): string
    {
        return $this->container->get('filePathBuilder')->createPath(FilePathBuilder::UPLOAD);
    }
}
