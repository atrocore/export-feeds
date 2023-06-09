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

use Espo\Core\EventManager\Event;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Exception;
use Espo\Core\Utils\Util;
use Espo\Entities\Attachment;
use Espo\ORM\EntityCollection;
use Export\Entities\ExportJob;
use Export\TwigFilter\AbstractTwigFilter;
use Export\TwigFunction\AbstractTwigFunction;
use Twig\TwigFilter;
use Twig\TwigFunction;

class ExportTypeSimple extends AbstractExportType
{
    protected $fullCollection = null;

    public function runExport(ExportJob $exportJob): Attachment
    {
        $attachmentCreatorName = 'export' . ucfirst($this->data['feed']['fileType']);
        if (!method_exists($this, $attachmentCreatorName)) {
            throw new Error('Unsupported file type.');
        }

        $attachment = $this->$attachmentCreatorName($exportJob);

        if ($exportJob->get('count') === 0) {
            $this->getEntityManager()->removeEntity($attachment);
            throw new BadRequest($this->translate('noDataFound', 'exceptions', 'ExportFeed'));
        }


        return $attachment;
    }

    public function renderTemplateContents(string $template, array $templateData): string
    {
        $templateData['config'] = $this->getConfig()->getData();
        $templateData['feedData'] = $this->data['feed'];

        $twig = new \Twig\Environment(new \Twig\Loader\ArrayLoader(['template' => $template]));
        foreach ($this->getMetadata()->get(['app', 'twigFilters'], []) as $alias => $className) {
            $filter = $this->getContainer()->get($className);
            if ($filter instanceof AbstractTwigFilter) {
                $filter->setFeedData($this->data['feed']);
                $twig->addFilter(new TwigFilter($alias, [$filter, 'filter']));
            }
        }

        foreach ($this->getMetadata()->get(['app', 'twigFunctions'], []) as $alias => $className) {
            $twigFunction = $this->getContainer()->get($className);
            if ($twigFunction instanceof AbstractTwigFunction && method_exists($twigFunction, 'run')) {
                $twigFunction->setFeedData($this->data['feed']);
                $twig->addFunction(new TwigFunction($alias, [$twigFunction, 'run']));
            }
        }

        return $twig->render('template', $templateData);
    }

    public function getFullCollection(): EntityCollection
    {
        if ($this->fullCollection === null) {
            $this->fullCollection = new EntityCollection();
            $offset = (int)$this->data['offset'];
            while (!empty($v = $this->getCollection($offset))) {
                $offset = $offset + $this->data['limit'];
                foreach ($v as $entity) {
                    $this->fullCollection->append($entity);
                }
            }
        }

        return $this->fullCollection;
    }

    protected function exportJson(ExportJob $exportJob): Attachment
    {
        if (!empty($this->data['feed']['separateJob'])) {
            $collection = $this->getCollection();
        } else {
            $collection = $this->getFullCollection();
        }

        $exportJob->set('count', count($collection));

        $contents = $this->renderTemplateContents((string)$this->data['feed']['template'], ['entities' => $collection]);

        if (!empty($contents)) {
            $array = @json_decode(preg_replace("/}[\n\s]*,[\n\s]*]/", "}]", $contents), true);
            if (!empty($array)) {
                $contents = json_encode($array);
            }
        }

        $repository = $this->getEntityManager()->getRepository('Attachment');

        // create attachment
        $attachment = $repository->get();
        $attachment->set('name', $this->getExportFileName('json'));
        $attachment->set('role', 'Export');
        $attachment->set('relatedType', 'ExportJob');
        $attachment->set('relatedId', $this->data['exportJobId']);
        $attachment->set('storage', 'UploadDir');
        $attachment->set('storageFilePath', $this->createPath());

        $this->beforeStore($this, $attachment, 'json');

        $fileName = $repository->getFilePath($attachment);

        $this->createDir($fileName);
        file_put_contents($fileName, $contents);

        $attachment->set('type', 'application/json');
        $attachment->set('size', \filesize($fileName));

        $this->getEntityManager()->saveEntity($attachment);

        return $attachment;
    }

    protected function exportXml(ExportJob $exportJob): Attachment
    {
        if (!empty($this->data['feed']['separateJob'])) {
            $collection = $this->getCollection();
        } else {
            $collection = $this->getFullCollection();
        }

        $exportJob->set('count', count($collection));

        $contents = $this->renderTemplateContents((string)$this->data['feed']['template'], ['entities' => $collection]);

        $repository = $this->getEntityManager()->getRepository('Attachment');

        // create attachment
        $attachment = $repository->get();
        $attachment->set('name', $this->getExportFileName('xml'));
        $attachment->set('role', 'Export');
        $attachment->set('relatedType', 'ExportJob');
        $attachment->set('relatedId', $this->data['exportJobId']);
        $attachment->set('storage', 'UploadDir');
        $attachment->set('storageFilePath', $this->createPath());

        $this->beforeStore($this, $attachment, 'xml');

        $fileName = $repository->getFilePath($attachment);

        $this->createDir($fileName);
        file_put_contents($fileName, $contents);

        $attachment->set('type', 'application/xml');
        $attachment->set('size', \filesize($fileName));

        $this->getEntityManager()->saveEntity($attachment);

        return $attachment;
    }

    protected function exportCsv(ExportJob $exportJob): Attachment
    {
        $repository = $this->getEntityManager()->getRepository('Attachment');

        // create attachment
        $attachment = $repository->get();
        $attachment->set('name', $this->getExportFileName('csv'));
        $attachment->set('role', 'Export');
        $attachment->set('relatedType', 'ExportJob');
        $attachment->set('relatedId', $this->data['exportJobId']);
        $attachment->set('storage', 'UploadDir');
        $attachment->set('storageFilePath', $this->createPath());

        $this->beforeStore($this, $attachment, 'csv');

        $data = $this->createCacheFile();
        $exportJob->set('count', $data['count']);
        $exportJob->set('data', array_merge($exportJob->getData(), $data));

        $this->storeCsvFile($exportJob->getData(), $repository->getFilePath($attachment));

        $attachment->set('type', 'text/csv');
        $attachment->set('size', \filesize($repository->getFilePath($attachment)));

        $this->getEntityManager()->saveEntity($attachment);

        return $this->exportAsZip($data['configuration'], $attachment);
    }

    protected function exportXlsx(ExportJob $exportJob): Attachment
    {
        if (!empty($this->data['feed']['sheets'])) {
            $sheets = $this->data['feed']['sheets'];
        } else {
            $sheets = [
                [
                    'name' => 'Sheet',
                    'configuration' => $this->data['feed']['data']['configuration'],
                    'entity' => $this->data['feed']['entity'],
                    'data' => $this->data['feed']['data'],
                ]
            ];
        }

        $repository = $this->getEntityManager()->getRepository('Attachment');

        // create attachment
        $attachment = $repository->get();
        $attachment->set('name', $this->getExportFileName('xlsx'));
        $attachment->set('role', 'Export');
        $attachment->set('relatedType', 'ExportJob');
        $attachment->set('relatedId', $this->data['exportJobId']);
        $attachment->set('storage', 'UploadDir');
        $attachment->set('storageFilePath', $this->createPath());

        $this->beforeStore($this, $attachment, 'xlsx');

        $fileName = $repository->getFilePath($attachment);

        $this->createDir($fileName);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $count = 0;
        foreach ($sheets as $k => $sheet) {
            $this->data['feed']['data']['configuration'] = $sheet['configuration'];
            $this->data['feed']['entity'] = $sheet['entity'];
            $this->data['feed']['data']['where'] = $sheet['data']['where'] ?? [];

            $data = $this->createCacheFile();

            $count += $data['count'];

            // prepare CSV filename
            $pathParts = explode('/', $repository->getFilePath($attachment));
            array_pop($pathParts);
            $pathParts[] = Util::generateId() . '.csv';
            $csvFileName = implode('/', $pathParts);

            $this->storeCsvFile($data, $csvFileName);

            // prepare CSV reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $reader->setDelimiter($this->getDelimiter());
            $reader->setEnclosure($this->getEnclosure());

            $myWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $sheet['name']);
            $spreadsheet->addSheet($myWorkSheet, $k);

            // load a CSV file and save as a XLS
            $reader->setSheetIndex($k);
            $reader->loadIntoExisting($csvFileName, $spreadsheet);

            // delete csv file
            unlink($csvFileName);
        }

        try {
            // delete default sheet
            $spreadsheet->removeSheetByIndex(count($sheets));
        } catch (\Throwable $e) {
            // ignore
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($fileName);

        $exportJob->set('count', $count);

        $attachment->set('type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $attachment->set('size', \filesize($repository->getFilePath($attachment)));

        $this->getEntityManager()->saveEntity($attachment);

        if (count($sheets) > 1) {
            $attachment = $this->exportAsZip($this->data['feed']['data']['configuration'], $attachment);
        }

        return $attachment;
    }

    protected function exportAsZip(array $configuration, Attachment $attachment): Attachment
    {
        $zipColumns = array_filter($configuration, function ($field) {
            return $field['zip'];
        });

        if (count($zipColumns) > 0) {
            $repository = $this->getEntityManager()->getRepository('Attachment');

            $zipAttachment = $repository->get();
            $zipAttachment->set('name', $this->getExportFileName('zip'));
            $zipAttachment->set('role', 'Export');
            $zipAttachment->set('relatedType', 'ExportJob');
            $zipAttachment->set('relatedId', $this->data['exportJobId']);
            $zipAttachment->set('storage', 'UploadDir');
            $zipAttachment->set('storageFilePath', $this->createPath());
            $zipAttachment->set('type', 'application/zip');
            $fileName = $repository->getFilePath($zipAttachment);
            $this->createDir($fileName);

            $za = new \ZipArchive();
            if ($za->open($fileName, \ZipArchive::CREATE) !== true) {
                throw new Exception("cannot open archive $fileName\n");
            }
            $za->addFile($attachment->getFilePath(), $attachment->get('name'));

            if (!empty($this->data['feed']['separateJob'])) {
                $collection = $this->getCollection();
            } else {
                $collection = $this->getFullCollection();
            }

            foreach ($zipColumns as $zipColumn) {
                $dir = $zipColumn['column'];
                $za->addEmptyDir($dir);
                foreach ($collection as $item) {
                    $field = $zipColumn['field'];
                    $type = $this->getMetadata()->get(['entityDefs', $item->getEntityType(), 'fields', $field, 'type']);
                    $foreignEntity = (string)$this->getMetadata()->get(['entityDefs', $item->getEntityType(), 'links', $field, 'entity']);

                    if ($type == "linkMultiple") {
                        $params = [];

                        if (!empty($sortBy)) {
                            $asc = $this->convertor->getMetadata()->get(['clientDefs', $item->getEntityType(), 'relationshipPanels', $field, 'asc'], true);
                            $params['sortBy'] = $sortBy;
                            $params['asc'] = !empty($asc);
                        }

                        if (!empty($zipColumn['sortFieldRelation'])) {
                            $params['sortBy'] = $zipColumn['sortFieldRelation'];
                            $params['asc'] = $zipColumn['sortOrderRelation'] !== 'DESC';
                        }

                        $params['offset'] = empty($zipColumn['offsetRelation']) ? 0 : (int)$zipColumn['offsetRelation'];
                        $params['maxSize'] = empty($zipColumn['limitRelation']) ? 20 : (int)$zipColumn['limitRelation'];

                        if (!empty($zipColumn['channelId'])) {
                            $params['exportByChannelId'] = $zipColumn['channelId'];
                        }

                        if (!empty($zipColumn['filterField']) && !empty($zipColumn['filterFieldValue'])) {
                            switch ($this->convertor->getMetadata()->get(['entityDefs', $foreignEntity, 'fields', $zipColumn['filterField'], 'type'])) {
                                case 'bool':
                                    switch ($zipColumn['filterFieldValue']) {
                                        case ['+']:
                                            $params['where'] = [['type' => 'isTrue', 'attribute' => $zipColumn['filterField']]];
                                            break;
                                        case ['-']:
                                            $params['where'] = [['type' => 'isFalse', 'attribute' => $zipColumn['filterField']]];
                                            break;
                                    }
                                    break;
                                case 'enum':
                                    $params['where'] = [
                                        [
                                            'type' => 'in',
                                            'attribute' => $zipColumn['filterField'],
                                            'value' => $zipColumn['filterFieldValue'],
                                        ]
                                    ];
                                    break;
                                case 'multiEnum':
                                    $params['where'] = [
                                        [
                                            'type' => 'arrayAnyOf',
                                            'attribute' => $zipColumn['filterField'],
                                            'value' => $zipColumn['filterFieldValue'],
                                        ]
                                    ];
                                    break;
                            }
                        }

                        if (!empty($zipColumn['searchFilter'])) {
                            $params['where'] = !empty($zipColumn['searchFilter']['where']) ? $zipColumn['searchFilter']['where'] : [];
                        }


                        $foreignResult = $this->convertor->findLinkedEntities($zipColumn['entity'], $item->get('id'), $field, $params);
                        $foreignList = [];
                        if (isset($foreignResult['collection'])) {
                            $foreignList = $foreignResult['collection']->toArray();
                        } elseif (isset($foreignResult['list'])) {
                            $foreignList = $foreignResult['list'];
                        }
                        foreach ($foreignList as $fe) {
                            $foreign = $this->convertor->getEntity('Attachment', $fe['fileId']);
                            $za->addFile($repository->getFilePath($foreign), $dir . '/' . $foreign->get('name'));
                        }
                    } else {
                        $linkId = $item->get($field . 'Id');
                        if ($foreignEntity === 'Attachment') {
                            $foreign = $this->convertor->getEntity($foreignEntity, $linkId);
                            $za->addFile($repository->getFilePath($foreign), $dir . '/' . $foreign->get('name'));
                        } else {
                            if ($foreignEntity === 'Asset') {
                                $foreign = $this->convertor->getEntity('Attachment', $foreign->get('fileId'));
                                $za->addFile($repository->getFilePath($foreign), $dir . '/' . $foreign->get('name'));
                            }
                        }
                    }
                }
            }
            $za->close();
            $zipAttachment->set('size', \filesize($repository->getFilePath($zipAttachment)));
            $this->getEntityManager()->saveEntity($zipAttachment);
            $this->getEntityManager()->removeEntity($attachment);
            return $zipAttachment;
        }

        return $attachment;
    }

    protected function prepareColumns(array $data): array
    {
        $columns = [];

        $cacheFile = fopen($data['fullFileName'], "r");
        while (($line = fgets($cacheFile)) !== false) {
            if (empty($line)) {
                continue;
            }
            $json = @json_decode($line, true);
            if (!is_array($json)) {
                continue;
            }

            foreach ($json as $rowNumber => $colData) {
                $n = 0;
                foreach ($colData as $colName => $colValue) {
                    $columns[$rowNumber . '_' . $colName] = [
                        'number' => $rowNumber,
                        'pos' => $rowNumber * 1000 + $n,
                        'name' => $colName
                    ];
                    $n++;
                }
            }
        }
        fclose($cacheFile);

        $result = [];

        // sorting columns
        $number = 0;
        while (count($columns) > 0) {
            foreach ($columns as $k => $row) {
                if ($row['number'] == $number) {
                    $result[] = $row;
                    unset($columns[$k]);
                }
            }
            $number++;
        }

        return $result;
    }

    protected function storeCsvFile(array $data, string $fileName): void
    {
        $columns = $this->prepareColumns($data);

        $this->createDir($fileName);

        $delimiter = $this->getDelimiter();
        $enclosure = $this->getEnclosure();

        $fp = fopen($fileName, "w");

        // prepare header
        if ($this->data['feed']['isFileHeaderRow']) {
            fputcsv($fp, array_column($columns, 'name'), $delimiter, $enclosure);
        }

        $cacheFile = fopen($data['fullFileName'], "r");
        while (($line = fgets($cacheFile)) !== false) {
            if (empty($line)) {
                continue;
            }

            $rowData = @json_decode($line, true);
            if (!is_array($rowData)) {
                continue;
            }

            $resultRow = [];
            foreach ($columns as $pos => $columnData) {
                $resultRow[$pos] = isset($rowData[$columnData['number']][$columnData['name']]) ? $rowData[$columnData['number']][$columnData['name']] : null;
            }

            fputcsv($fp, $resultRow, $delimiter, $enclosure);
        }
        fclose($cacheFile);

        rewind($fp);
        fclose($fp);

        // remove cache file
        unlink($data['fullFileName']);
    }

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

    protected function beforeStore(ExportTypeSimple $typeService, Attachment $attachment, string $format)
    {
        $event = new Event(['data' => $this->data, 'typeService' => $typeService, 'attachment' => $attachment, 'extension' => $format]);
        $this->getContainer()->get('eventManager')->dispatch('ExportTypeSimpleService', 'beforeStore', $event);
    }

    public function getUrlColumns()
    {
        $urlColumns = [];
        $data = $this->data['feed']['data'];

        foreach ($data['configuration'] as $row) {
            if (is_array($row['exportBy']) && $row['exportBy'][0] === "url") {
                $urlColumns[] = $row['column'];
            }
        }
        return $urlColumns;
    }

    public function exportEasyCatalogJson(): array
    {
        $this->convertor = $this->getDataConvertor();
        $data = $this->createCacheFile(true);
        $columns = $this->prepareColumns($data);

        $result = [];
        $cacheFile = fopen($data['fullFileName'], "r");
        while (($line = fgets($cacheFile)) !== false) {
            if (empty($line)) {
                continue;
            }

            $rowData = @json_decode($line, true);
            if (!is_array($rowData)) {
                continue;
            }

            $resultRow = [];
            foreach ($columns as $pos => $columnData) {
                $resultRow[$columnData['name']] = isset($rowData[$columnData['number']][$columnData['name']]) ? $rowData[$columnData['number']][$columnData['name']] : null;
            }
            $result[] = $resultRow;

        }
        fclose($cacheFile);

        return $result;
    }
}
