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
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;
use Espo\Entities\Attachment;
use Espo\ORM\Entity;
use Export\DataConvertor\Base;
use Treo\Core\FilePathBuilder;

/**
 * Type Simple
 */
class Simple extends AbstractType
{
    /**
     * @var Base|null
     */
    private $dataConvertor = null;

    /**
     * @var array
     */
    private $languages = [];

    /**
     * @var array
     */
    private $foundAttrs = [];

    /**
     * @param string   $scope
     * @param Metadata $metadata
     * @param Language $language
     *
     * @return array
     */
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

    /**
     * @return Attachment
     * @throws Error
     */
    public function export(): Attachment
    {
        if (empty($this->data['feed']['fileType'])) {
            $this->data['feed']['fileType'] = 'xlsx';
        }

        $attachmentCreatorName = 'export' . ucfirst($this->data['feed']['fileType']);
        if (!method_exists($this, $attachmentCreatorName)) {
            throw new Error('Unsupported file type.');
        }

        return $this->$attachmentCreatorName($this->getData());
    }

    /**
     * @param array $data
     *
     * @return Attachment
     */
    protected function exportCsv(array $data): Attachment
    {
        $repository = $this->getEntityManager()->getRepository('Attachment');

        // create attachment
        $attachment = $repository->get();
        $attachment->set('name', $this->getExportFileName('csv'));
        $attachment->set('role', 'Export');
        $attachment->set('relatedType', 'ExportResult');
        $attachment->set('relatedId', $this->data['id']);
        $attachment->set('storage', 'UploadDir');
        $attachment->set('storageFilePath', $this->container->get('filePathBuilder')->createPath(FilePathBuilder::UPLOAD));

        $this->storeCsvFile($data, $repository->getFilePath($attachment));

        $attachment->set('type', 'text/csv');
        $attachment->set('size', \filesize($repository->getFilePath($attachment)));

        $this->getEntityManager()->saveEntity($attachment);

        return $attachment;
    }

    /**
     * @param array $data
     *
     * @return Attachment
     */
    protected function exportXlsx(array $data): Attachment
    {
        $repository = $this->getEntityManager()->getRepository('Attachment');

        // create attachment
        $attachment = $repository->get();
        $attachment->set('name', $this->getExportFileName('xlsx'));
        $attachment->set('role', 'Export');
        $attachment->set('relatedType', 'ExportResult');
        $attachment->set('relatedId', $this->data['id']);
        $attachment->set('storage', 'UploadDir');
        $attachment->set('storageFilePath', $this->container->get('filePathBuilder')->createPath(FilePathBuilder::UPLOAD));

        $this->storeXlsxFile($data, $repository->getFilePath($attachment));

        $attachment->set('type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $attachment->set('size', \filesize($repository->getFilePath($attachment)));

        $this->getEntityManager()->saveEntity($attachment);

        return $attachment;
    }

    /**
     * @param string $extension
     *
     * @return string
     */
    protected function getExportFileName(string $extension): string
    {
        return $this->data['feed']['name'] . '_' . time() . '.' . $extension;
    }

    /**
     * @return array
     * @throws BadRequest
     * @throws Error
     * @throws NotFound
     */
    protected function getData(): array
    {
        // prepare export feed data
        $data = $this->getFeedData();

        if (empty($data['allFields'])) {
            $configuration = $data['configuration'];
        } else {
            $configuration = self::getAllFieldsConfiguration($data['entity'], $this->getMetadata(), $this->container->get('language'));
        }

        if (!empty($this->data['exportByChannelId'])) {
            $channel = $this->getEntityManager()->getEntity('Channel', $this->data['exportByChannelId']);
            if (empty($channel)) {
                throw new BadRequest('No such channel found.');
            }
            $this->data['channelLocales'] = $channel->get('locales');
        }

        $resultData = [];
        foreach ($this->getRecords() as $record) {
            $resultData[$record['id']] = [];
            foreach ($configuration as $k => $row) {
                $row = $this->prepareRow($row);

                if (!empty($row['channelLocales']) && !empty($row['locale']) && !in_array($row['locale'], $row['channelLocales'])) {
                    continue 1;
                }

                $resultData[$record['id']][$k] = $this->getDataConvertor($data['entity'])->convert($record, $row);
            }
        }

        if (empty($resultData)) {
            throw new BadRequest($this->translate('noDataFound', 'exceptions', 'ExportFeed'));
        }

        // prepare columns
        $columns = [];
        foreach ($resultData as $rows) {
            foreach ($rows as $rowNumber => $rowData) {
                $n = 0;
                foreach ($rowData as $colName => $value) {
                    $columns[$rowNumber . '_' . $colName] = [
                        'number' => $rowNumber,
                        'pos'    => $rowNumber * 1000 + $n,
                        'name'   => $colName,
                        'label'  => $this->getDataConvertor($data['entity'])->getColumnLabel($colName, $this->data, $rowNumber)
                    ];
                    $n++;
                }
            }
        }

        // sorting
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

        $result = ['columns' => $sortedColumns, 'data' => []];
        foreach ($resultData as $rowData) {
            $resultRow = [];
            foreach ($sortedColumns as $pos => $columnData) {
                if (isset($rowData[$columnData['number']][$columnData['name']])) {
                    $resultRow[$pos] = $rowData[$columnData['number']][$columnData['name']];
                } else {
                    $resultRow[$pos] = $configuration[0]['nullValue'];
                }
            }
            $result['data'][] = $resultRow;
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getRecords(): array
    {
        $maxSize = 200;

        $data = $this->getFeedData();

        $params = [
            'sortBy'  => 'id',
            'asc'     => true,
            'offset'  => 0,
            'maxSize' => $maxSize,
            'where'   => !empty($data['where']) ? $data['where'] : []
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

        $records = [];
        while (true) {
            $result = $this->getService($data['entity'])->findEntities($params);

            $list = isset($result['collection']) ? $result['collection']->toArray() : $result['list'];

            if (count($list) == 0) {
                break;
            }

            $records = array_merge($records, $list);

            $params['offset'] = $params['offset'] + $maxSize;
        }

        return $records;
    }

    /**
     * @param string $scope
     *
     * @return Base
     * @throws Error
     */
    protected function getDataConvertor(string $scope): Base
    {
        if (empty($this->dataConvertor)) {
            $className = "Export\\DataConvertor\\" . $scope;

            if (!class_exists($className)) {
                $className = Base::class;
            }

            if (!is_a($className, Base::class, true)) {
                throw new Error($className . ' should be instance of ' . Base::class);
            }

            $this->dataConvertor = new $className($this->container);
        }

        return $this->dataConvertor;
    }

    /**
     * @param array $row
     *
     * @return array
     */
    protected function prepareRow(array $row): array
    {
        $feedData = $this->getFeedData();

        $row['channelId'] = isset($this->data['exportByChannelId']) ? $this->data['exportByChannelId'] : '';
        $row['delimiter'] = !empty($feedData['delimiter']) ? $feedData['delimiter'] : ',';
        $row['emptyValue'] = !empty($feedData['emptyValue']) ? $feedData['emptyValue'] : '';
        $row['nullValue'] = !empty($feedData['nullValue']) ? $feedData['nullValue'] : 'Null';
        $row['markForNotLinkedAttribute'] = !empty($feedData['markForNotLinkedAttribute']) ? $feedData['markForNotLinkedAttribute'] : '--';
        $row['entity'] = $feedData['entity'];

        if (!empty($this->data['channelLocales'])) {
            $row['channelLocales'] = $this->data['channelLocales'];

            if (empty($row['attributeId'])) {
                $row['locale'] = $this->getMetadata()->get(['entityDefs', $feedData['entity'], 'fields', $row['field'], 'multilangLocale']);
                if ($this->getMetadata()->get(['entityDefs', $feedData['entity'], 'fields', $row['field'], 'isMultilang'])) {
                    $row['locale'] = 'mainLocale';
                }
            } else {
                $attribute = $this->getAttributeById($row['attributeId']);
                if (empty($attribute->get('isMultilang'))) {
                    $row['locale'] = null;
                }
            }
        }
        $row['column'] = $this->getColumnName($row, $feedData['entity']);

        return $row;
    }

    /**
     * @return array
     */
    protected function getFeedData(): array
    {
        return Json::decode(Json::encode($this->data['feed']['data']), true);
    }

    /**
     * @param string $fileName
     */
    protected function createDir(string $fileName): void
    {
        $parts = explode('/', $fileName);
        array_pop($parts);
        $dir = implode('/', $parts);

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            sleep(1);
        }
    }

    /**
     * @param array  $data
     * @param string $fileName
     */
    protected function storeCsvFile(array $data, string $fileName): void
    {
        $this->createDir($fileName);

        // prepare settings
        $delimiter = $this->data['feed']['csvFieldDelimiter'];
        $enclosure = ($this->data['feed']['csvTextQualifier'] == 'doubleQuote') ? '"' : "'";

        /**
         * Prepare data
         */
        $rows = [];
        foreach ($data['data'] as $row) {
            foreach ($row as $key => $field) {
                if (is_array($field)) {
                    $row[$key] = '[' . implode(",", $field) . ']';
                }
            }
            $rows[] = array_values($row);
        }

        // open file
        $fp = fopen($fileName, "w");

        // prepare header
        if ($this->data['feed']['isFileHeaderRow']) {
            fputcsv($fp, array_column($data['columns'], 'label'), $delimiter, $enclosure, '~~~~~');
        }

        // prepare rows
        foreach ($rows as $item) {
            fputcsv($fp, $item, $delimiter, $enclosure, '~~~~~');
        }

        // rewind
        rewind($fp);

        // close file
        fclose($fp);
    }

    /**
     * @param array  $data
     * @param string $fileName
     *
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    protected function storeXlsxFile(array $data, string $fileName): void
    {
        $this->createDir($fileName);

        $csvFileName = str_replace('.xlsx', '.csv', $fileName);

        $this->storeCsvFile($data, $csvFileName);

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();

        // set CSV parsing options
        $reader->setDelimiter(";");
        $reader->setEnclosure('"');
        $reader->setSheetIndex(0);

        // load a CSV file and save as a XLS
        $spreadsheet = $reader->load($csvFileName);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($fileName);

        // delete csv file
        unlink($csvFileName);
    }

    protected function getColumnName(array $row, string $entity): string
    {
        // for attributes
        if (!empty($row['attributeId'])) {
            $attribute = $this->getAttributeById($row['attributeId']);

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

    protected function getLanguage(string $locale): Language
    {
        if (!isset($this->languages[$locale])) {
            $this->languages[$locale] = new Language($this->container, $locale);
        }

        return $this->languages[$locale];
    }

    protected function getAttributeById(string $id): Entity
    {
        if (!isset($this->foundAttrs[$id])) {
            $attribute = $this->getEntityManager()->getEntity('Attribute', $id);
            if (empty($attribute)) {
                throw new NotFound("Can't find provided attribute.");
            }
            $this->foundAttrs[$id] = $attribute;
        }

        return $this->foundAttrs[$id];
    }
}
