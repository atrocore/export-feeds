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

namespace Export\Services;

use Espo\Core\Container;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;
use Espo\Entities\Attachment;
use Espo\ORM\EntityManager;
use Espo\Services\Record;
use Export\DataConvertor\Convertor;
use Export\Entities\ExportJob;
use Treo\Core\FilePathBuilder;

abstract class AbstractExportType extends \Espo\Core\Services\Base
{
    protected array $data;

    private array $services = [];

    private array $languages = [];

    private array $pavs = [];

    private int $iteration = 0;

    private Convertor $convertor;

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

    public function getCount(array $data): int
    {
        $this->setData($data);

        $result = $this->getEntityService()->findEntities($this->getSelectParams());

        if (empty($result['total'])) {
            throw new BadRequest($this->translate('noDataFound', 'exceptions', 'ExportFeed'));
        }

        return $result['total'];
    }

    public function export(array $data, ExportJob $exportJob): Attachment
    {
        $this->setData($data);
        $this->convertor = $this->getDataConvertor();
        $this->createCacheFile($exportJob);

        return $this->runExport($exportJob->getData());
    }

    abstract public function runExport(array $jobMetadata): Attachment;

    protected function setData(array $data): void
    {
        $this->data = Json::decode(Json::encode($data), true);
    }

    protected function getExportFileName(string $extension): string
    {
        $fileName = str_replace(' ', '_', strtolower($this->data['feed']['name']));

        if (!empty($this->data['iteration'])) {
            $fileName .= '_' . $this->data['iteration'];
        }

        $fileName .= '_' . date('YmdHis') . '.' . $extension;

        return $fileName;
    }

    protected function prepareRow(array $row): array
    {
        $feedData = $this->data['feed']['data'];

        $row['channelId'] = isset($this->data['exportByChannelId']) ? $this->data['exportByChannelId'] : '';
        $row['delimiter'] = !empty($feedData['delimiter']) ? $feedData['delimiter'] : ',';
        $row['emptyValue'] = !empty($feedData['emptyValue']) ? $feedData['emptyValue'] : '';
        $row['nullValue'] = !empty($feedData['nullValue']) ? $feedData['nullValue'] : 'Null';
        $row['markForNotLinkedAttribute'] = !empty($feedData['markForNotLinkedAttribute']) ? $feedData['markForNotLinkedAttribute'] : '--';
        $row['decimalMark'] = !empty($feedData['decimalMark']) ? $feedData['decimalMark'] : ',';
        $row['thousandSeparator'] = !empty($feedData['thousandSeparator']) ? $feedData['thousandSeparator'] : '';
        $row['fieldDelimiterForRelation'] = !empty($feedData['fieldDelimiterForRelation']) ? $feedData['fieldDelimiterForRelation'] : \Export\DataConvertor\Convertor::DELIMITER;
        $row['entity'] = $feedData['entity'];

        if (!empty($this->data['channelLocales'])) {
            $row['channelLocales'] = $this->data['channelLocales'];

            if (empty($row['attributeId'])) {
                $row['locale'] = $this->getMetadata()->get(['entityDefs', $feedData['entity'], 'fields', $row['field'], 'multilangLocale']);
                if ($this->getMetadata()->get(['entityDefs', $feedData['entity'], 'fields', $row['field'], 'isMultilang'])) {
                    $row['locale'] = 'mainLocale';
                }
            } else {
                $attribute = $this->convertor->getEntity('Attribute', $row['attributeId']);
                if (empty($attribute->get('isMultilang'))) {
                    $row['locale'] = null;
                }
            }
        }
        $row['column'] = $this->getColumnName($row, $feedData['entity']);

        return $row;
    }

    protected function getColumnName(array $row, string $entity): string
    {
        // for attributes
        if (!empty($row['attributeId'])) {
            $attribute = $this->convertor->getEntity('Attribute', $row['attributeId']);

            $locale = $row['locale'];
            if ($locale === 'mainLocale') {
                $locale = '';
            }

            if (empty($row['columnType']) || $row['columnType'] == 'name') {
                $name = 'name';

                if (!empty($attribute->get('isMultilang')) && !empty($locale)) {
                    $name = Util::toCamelCase(strtolower($name . '_' . $locale));
                }

                return $attribute->get($name);
            }

            if ($row['columnType'] == 'internal') {
                $value = $attribute->get('name');
                if (!empty($locale)) {
                    $value .= ' › ' . $locale;
                }

                return $value;
            }
        }

        if (empty($row['columnType']) || $row['columnType'] == 'name') {
            $locale = $this->getMetadata()->get(['entityDefs', $entity, 'fields', $row['field'], 'multilangLocale']);
            if ($locale) {
                $originField = $this->getMetadata()->get(['entityDefs', $entity, 'fields', $row['field'], 'multilangField']);
                return $this->getLanguage($locale)->translate($originField, 'fields', $entity);
            } else {
                if (!empty($row['channelLocales'][0]) && $row['channelLocales'][0] !== 'mainLocale') {
                    return $this->getLanguage($row['channelLocales'][0])->translate($row['field'], 'fields', $entity);
                } else {
                    return $this->translate($row['field'], 'fields', $entity);
                }
            }
        }

        if ($row['columnType'] == 'internal') {
            return $this->translate($row['field'], 'fields', $entity);
        }

        return $row['column'];
    }

    protected function getContainer(): Container
    {
        return $this->getInjection('container');
    }

    protected function getDataConvertor(): Convertor
    {
        $className = "Export\\DataConvertor\\" . $this->data['feed']['data']['entity'];

        if (!class_exists($className)) {
            $className = Convertor::class;
        }

        if (!is_a($className, Convertor::class, true)) {
            throw new Error($className . ' should be instance of ' . Convertor::class);
        }

        return new $className($this->getContainer());
    }

    protected function getSelectParams(): array
    {
        $params = [
            'sortBy'  => 'id',
            'asc'     => true,
            'offset'  => 0,
            'maxSize' => 1,
            'where'   => !empty($this->data['feed']['data']['where']) ? $this->data['feed']['data']['where'] : []
        ];

        if (!empty($this->data['exportByChannelId'])) {
            if ($this->data['feed']['data']['entity'] == 'Product') {
                $params['where'][] = [
                    'type'  => 'bool',
                    'value' => ['activeForChannel'],
                    'data'  => ['activeForChannel' => $this->data['exportByChannelId']]
                ];
            } else {
                $links = $this->getMetadata()->get(['entityDefs', $this->data['feed']['data']['entity'], 'links'], []);
                foreach ($links as $link => $linkData) {
                    if ($linkData['entity'] == 'Channel') {
                        if ($linkData['type'] == 'hasMany') {
                            $params['where'][] = [
                                'type'      => 'linkedWith',
                                'attribute' => $link,
                                'value'     => [$this->data['exportByChannelId']]
                            ];
                        }
                        if ($linkData['type'] == 'belongsTo') {
                            $params['where'][] = [
                                'type'      => 'equals',
                                'attribute' => $link . 'Id',
                                'value'     => [$this->data['exportByChannelId']]
                            ];
                        }
                    }
                }
            }
        }

        return $params;
    }

    protected function getRecords(): array
    {
        if (!empty($this->data['feed']['separateJob']) && !empty($this->iteration)) {
            return [];
        }

        $params = $this->getSelectParams();
        $params['offset'] = $this->data['offset'];
        $params['maxSize'] = $this->data['limit'];

        $result = $this->getEntityService()->findEntities($params);

        $list = isset($result['collection']) ? $result['collection']->toArray() : $result['list'];

        // caching ProductAttributeValues if Product
        if ($this->data['feed']['entity'] === 'Product') {
            $this->loadPavs(array_column($list, 'id'));
        }

        $this->data['offset'] = $this->data['offset'] + $this->data['limit'];
        $this->iteration++;

        return $list;
    }

    protected function loadPavs(array $productsIds): void
    {
        $this->pavs = [];

        $pavParams = [
            'sortBy'  => 'id',
            'offset'  => 0,
            'maxSize' => \PHP_INT_MAX,
            'where'   => [
                [
                    'type'      => 'equals',
                    'attribute' => 'productId',
                    'value'     => $productsIds
                ]
            ]
        ];

        $selectParams = $this->getSelectManager('ProductAttributeValue')->getSelectParams($pavParams, true, true);
        foreach (['customJoin', 'additionalSelectColumns', 'customWhere'] as $key) {
            if (isset($selectParams[$key])) {
                unset($selectParams[$key]);
            }
        }

        $selectFields = ['id', 'productId', 'attributeId', 'scope', 'channelId', 'value', 'data'];
        if ($this->getConfig()->get('isMultilangActive', false) && !empty($locales = $this->getConfig()->get('inputLanguageList', []))) {
            foreach ($locales as $locale) {
                $selectFields[] = Util::toCamelCase('value_' . strtolower($locale));
            }
        }

        $pavs = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->select($selectFields)
            ->find($selectParams)
            ->toArray();

        if (!empty($pavs)) {
            $attrs = $this
                ->getEntityManager()
                ->getRepository('Attribute')
                ->select(['id', 'name', 'code', 'type'])
                ->where(['id' => array_column($pavs, 'attributeId')])
                ->find()
                ->toArray();
            $preparedAttrs = [];
            foreach ($attrs as $attr) {
                $preparedAttrs[$attr['id']] = $attr;
            }

            foreach ($pavs as $pav) {
                $row = $pav;
                $row['attributeName'] = $preparedAttrs[$pav['attributeId']]['name'];
                $row['attributeCode'] = $preparedAttrs[$pav['attributeId']]['code'];
                $row['attributeType'] = $preparedAttrs[$pav['attributeId']]['type'];

                $this->pavs[$pav['productId']][] = $row;
            }
        }
    }

    protected function createCacheFile(ExportJob $exportJob): string
    {
        // prepare export feed data
        $data = $this->data['feed']['data'];

        $configuration = $data['configuration'];

        if (!empty($this->data['exportByChannelId'])) {
            $channel = $this->getEntityManager()->getEntity('Channel', $this->data['exportByChannelId']);
            if (empty($channel)) {
                throw new BadRequest('No such channel found.');
            }
            $this->data['channelLocales'] = $channel->get('locales');
        }

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
        $count = 0;

        while (!empty($records = $this->getRecords())) {
            foreach ($records as $record) {
                $pushRow = [];
                foreach ($configuration as $rowNumber => $row) {
                    $row = $this->prepareRow($row);
                    if ($row['entity'] === 'Product') {
                        $row['pavs'] = isset($this->pavs[$record['id']]) ? $this->pavs[$record['id']] : [];
                    }

                    if (!empty($row['channelLocales']) && !empty($row['locale']) && !in_array($row['locale'], $row['channelLocales'])) {
                        continue 1;
                    }

                    $converted = $this->convertor->convert($record, $row);

                    $n = 0;
                    foreach ($converted as $colName => $value) {
                        $columns[$rowNumber . '_' . $colName] = [
                            'number' => $rowNumber,
                            'pos'    => $rowNumber * 1000 + $n,
                            'name'   => $colName,
                            'label'  => $this->convertor->getColumnLabel($colName, $this->data, $rowNumber)
                        ];
                        $n++;
                    }

                    $pushRow[] = $converted;
                }

                $pushContent = Json::encode($pushRow) . PHP_EOL;
                fwrite($file, $pushContent);
                $count++;
            }
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

        $exportJob->set('count', $count);
        $exportJob->set('data', array_merge($exportJob->getData(), ['columns' => $sortedColumns, 'fullFileName' => $fullFileName]));

        return $fullFileName;
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->getContainer()->get('entityManager');
    }

    protected function getService(string $serviceName): Record
    {
        if (!isset($this->services[$serviceName])) {
            $this->services[$serviceName] = $this->getContainer()->get('serviceFactory')->create($serviceName);
        }

        return $this->services[$serviceName];
    }

    protected function getEntityService(): Record
    {
        return $this->getService($this->data['feed']['entity']);
    }

    protected function getConfig(): Config
    {
        return $this->getContainer()->get('config');
    }

    protected function getMetadata(): Metadata
    {
        return $this->getContainer()->get('metadata');
    }

    protected function translate(string $key, string $tab, string $scope = 'Global'): string
    {
        return $this->getContainer()->get('language')->translate($key, $tab, $scope);
    }

    protected function getSelectManager(string $name): \Espo\Core\SelectManagers\Base
    {
        return $this->getContainer()->get('selectManagerFactory')->create($name);
    }

    protected function getLanguage(string $locale): Language
    {
        if (!isset($this->languages[$locale])) {
            $this->languages[$locale] = new Language($this->getContainer(), $locale);
        }

        return $this->languages[$locale];
    }

    protected function createPath(): string
    {
        return $this->getContainer()->get('filePathBuilder')->createPath(FilePathBuilder::UPLOAD);
    }

    protected function init()
    {
        parent::init();

        $this->addDependency('container');
    }
}
