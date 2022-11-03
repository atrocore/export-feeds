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
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

declare(strict_types=1);

namespace Export\Services;

use Espo\Core\Container;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\FilePathBuilder;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;
use Espo\Entities\Attachment;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;
use Espo\Services\Record;
use Export\DataConvertor\Convertor;
use Export\Entities\ExportJob;

abstract class AbstractExportType extends \Espo\Core\Services\Base
{
    protected array $data;

    protected Convertor $convertor;

    private array $services = [];

    private array $languages = [];

    private array $pavs = [];

    private int $iteration = 0;

    private array $attributes = [];

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
            }

            if ($data['type'] === 'currency') {
                $row['mask'] = "{{value}} {{currency}}";
            }

            if ($data['type'] === 'unit') {
                $row['mask'] = "{{value}} {{unit}}";
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

        if ($this->getContainer()->get('acl')->check($this->data['feed']['entity'], 'read')) {
            $result = $this->getEntityService()->findEntities($this->getSelectParams());
        }

        if (empty($result['total'])) {
            throw new BadRequest($this->translate('noDataFound', 'exceptions', 'ExportFeed'));
        }

        return $result['total'];
    }

    public function export(array $data, ExportJob $exportJob): Attachment
    {
        $this->setData($data);
        $this->convertor = $this->getDataConvertor();

        return $this->runExport($exportJob);
    }

    abstract public function runExport(ExportJob $exportJob): Attachment;

    protected function setData(array $data): void
    {
        $this->data = Json::decode(Json::encode($data), true);
    }

    protected function getExportFileName(string $extension): string
    {
        $fileName = preg_replace("/[^a-z0-9.!?]/", '', mb_strtolower($this->data['feed']['name']));

        if (!empty($this->data['iteration'])) {
            $fileName .= '_' . $this->data['iteration'];
        }

        $fileName .= '_' . date('YmdHis') . '.' . $extension;

        return $fileName;
    }

    protected function prepareRow(array $row): array
    {
        $feedData = $this->data['feed']['data'];

        if (empty($row['scope']) || $row['scope'] !== 'Channel') {
            $row['channelId'] = '';
        }

        $row['delimiter'] = !empty($feedData['delimiter']) ? $feedData['delimiter'] : ',';
        $row['emptyValue'] = !empty($feedData['emptyValue']) ? $feedData['emptyValue'] : '';
        $row['nullValue'] = !empty($feedData['nullValue']) ? $feedData['nullValue'] : 'Null';
        $row['markForNotLinkedAttribute'] = !empty($feedData['markForNotLinkedAttribute']) ? $feedData['markForNotLinkedAttribute'] : '--';
        $row['decimalMark'] = !empty($feedData['decimalMark']) ? $feedData['decimalMark'] : ',';
        $row['thousandSeparator'] = !empty($feedData['thousandSeparator']) ? $feedData['thousandSeparator'] : '';
        $row['fieldDelimiterForRelation'] = !empty($feedData['fieldDelimiterForRelation']) ? $feedData['fieldDelimiterForRelation'] : '|';
        $row['entity'] = $feedData['entity'];
        $row['column'] = $this->getColumnName($row, $feedData['entity']);

        if ($row['locale'] === 'mainLocale') {
            $row['locale'] = 'main';
        }

        return $row;
    }

    protected function getColumnName(array $row, string $entity): string
    {
        // for attributes
        if (!empty($row['attributeId'])) {
            $attribute = $this->getAttribute($row['attributeId']);

            $locale = $row['locale'];
            if ($locale === 'mainLocale') {
                $locale = '';
            }

            if (empty($row['columnType']) || $row['columnType'] == 'name') {
                $name = 'name';

                if (!empty($attribute->get('isMultilang')) && !empty($locale)) {
                    $name = Util::toCamelCase(strtolower($name . '_' . $locale));
                }

                return (string)$attribute->get($name);
            }

            if ($row['columnType'] == 'internal') {
                $value = (string)$attribute->get('name');
                if (!empty($locale)) {
                    $value .= ' / ' . $locale;
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
        $className = "Export\\DataConvertor\\{$this->data['feed']['data']['entity']}Convertor";

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
            'sortBy'      => 'id',
            'asc'         => true,
            'offset'      => 0,
            'maxSize'     => 1,
            'where'       => !empty($this->data['feed']['data']['where']) ? $this->data['feed']['data']['where'] : [],
            'withDeleted' => !empty($this->data['feed']['data']['withDeleted']),
        ];

        return $params;
    }

    protected function getRecords(): array
    {
        if (!empty($this->data['feed']['separateJob']) && !empty($this->iteration)) {
            return [];
        }

        if (!$this->getContainer()->get('acl')->check($this->data['feed']['entity'], 'read')) {
            return [];
        }

        $params = $this->getSelectParams();
        $params['offset'] = $this->data['offset'];
        $params['maxSize'] = $this->data['limit'];
        $params['withDeleted'] = !empty($this->data['feed']['data']['withDeleted']);

        if (!empty($this->data['feed']['sortOrderField'])) {
            $params['sortBy'] = $this->data['feed']['sortOrderField'];
            if ($this->getMetadata()->get(['entityDefs', $this->data['feed']['entity'], 'fields', $params['sortBy'], 'type']) === 'link') {
                $params['sortBy'] .= 'Id';
            }
            $params['asc'] = true;
            if (!empty($this->data['feed']['sortOrderDirection']) && $this->data['feed']['sortOrderDirection'] !== 'ASC') {
                $params['asc'] = false;
            }
        }

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

    protected function getCollection(): ?EntityCollection
    {
        if (!empty($this->data['feed']['separateJob']) && !empty($this->iteration)) {
            return null;
        }

        if (!$this->getContainer()->get('acl')->check($this->data['feed']['entity'], 'read')) {
            return null;
        }

        $params = $this->getSelectParams();
        $params['offset'] = $this->data['offset'];
        $params['maxSize'] = $this->data['limit'];
        $params['withDeleted'] = !empty($this->data['feed']['data']['withDeleted']);

        $this->data['offset'] = $this->data['offset'] + $this->data['limit'];
        $this->iteration++;

        $result = $this->getEntityService()->findEntities($params);
        if (isset($result['collection']) && count($result['collection']) > 0) {
            return $result['collection'];
        }

        return null;
    }

    protected function loadPavs(array $productsIds): void
    {
        $this->pavs = [];

        /**
         * @deprecated This only for pim < 1.4.0
         */
        if (empty($this->getMetadata()->get(['entityDefs', 'ProductAttributeValue', 'fields', 'boolValue']))) {
            foreach ($productsIds as $productId) {
                $params = ['select' => ['id', 'productId', 'attributeId', 'attributeName', 'scope', 'channelId', 'value', 'data', 'locale', 'isLocale']];
                if ($this->getConfig()->get('isMultilangActive', false) && !empty($locales = $this->getConfig()->get('inputLanguageList', []))) {
                    foreach ($locales as $locale) {
                        $params['select'][] = Util::toCamelCase('value_' . strtolower($locale));
                    }
                }
                $pavs = $this->getService('Product')->findLinkedEntities($productId, 'productAttributeValues', $params);
                $this->pavs[$productId] = [];
                if (!empty($pavs['total'])) {
                    if (isset($pavs['list'])) {
                        $this->pavs[$productId] = $pavs['list'];
                    } elseif (isset($pavs['collection'])) {
                        $this->pavs[$productId] = $pavs['collection']->toArray();
                    }
                }
            }
            return;
        }

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

        $selectFields = [
            'id',
            'productId',
            'attributeId',
            'scope',
            'channelId',
            'language',
            'boolValue',
            'dateValue',
            'datetimeValue',
            'intValue',
            'floatValue',
            'varcharValue',
            'textValue',
            'attributeType',
            'data'
        ];

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
                ->select(['id', 'name', 'code', 'type', 'isMultilang'])
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
                $row['isAttributeMultiLang'] = !empty($preparedAttrs[$pav['attributeId']]['isMultilang']);
                $this->pavs[$pav['productId']][] = $row;
            }
        }
    }

    protected function createCacheFile(ExportJob $exportJob): string
    {
        // prepare export feed data
        $data = $this->data['feed']['data'];

        $configuration = $data['configuration'];

        // prepare full file name
        $fileName = "{$this->data['exportJobId']}.txt";
        $filePath = $this->createPath();
        $fullFilePath = $this->getConfig()->get('filesPath', 'upload/files/') . $filePath;
        Util::createDir($fullFilePath);

        $jobMetadata = [
            'configuration' => [],
            'fullFileName'  => $fullFilePath . '/' . $fileName
        ];

        foreach ($configuration as $rowNumber => $row) {
            $jobMetadata['configuration'][$rowNumber] = $this->prepareRow($row);
        }

        // clearing file if it needs
        file_put_contents($jobMetadata['fullFileName'], '');

        $file = fopen($jobMetadata['fullFileName'], 'a');

        $count = 0;
        while (!empty($records = $this->getRecords())) {
            foreach ($records as $record) {
                if ($this->data['feed']['entity'] === 'Product') {
                    $record['pavs'] = isset($this->pavs[$record['id']]) ? $this->pavs[$record['id']] : [];
                    if (!empty($record['pavs'])) {
                        $record['pavs'] = $this->preparePavsForOutput($record['pavs']);
                    }
                }

                fwrite($file, Json::encode($record) . PHP_EOL);
                $count++;
            }
        }

        fclose($file);

        $exportJob->set('count', $count);
        $exportJob->set('data', array_merge($exportJob->getData(), $jobMetadata));

        return $jobMetadata['fullFileName'];
    }

    protected function getAttribute(string $id): Entity
    {
        if (!isset($this->attributes[$id])) {
            $this->attributes[$id] = $this->getEntityManager()->getEntity('Attribute', $id);
        }

        return $this->attributes[$id];
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

    protected function preparePavsForOutput(array $pavs): array
    {
        $productService = $this->getService('Product');
        if (!method_exists($productService, 'preparePavsForOutput')) {
            return $pavs;
        }

        $collection = new EntityCollection();
        foreach ($pavs as $v) {
            $pav = $this->getEntityManager()->getEntity('ProductAttributeValue');
            $pav->set($v);
            $collection->append($pav);
        }

        $result = $productService->preparePavsForOutput($collection)->toArray();
        foreach ($result as $key => $pav) {
            foreach ($pavs as $v) {
                if ($v['id'] == $pav['id']) {
                    $result[$key]['isAttributeMultiLang'] = $v['isAttributeMultiLang'];
                    break;
                }
            }
        }

        return $result;
    }
}
