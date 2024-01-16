<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

declare(strict_types=1);

namespace Export\Services;

use Atro\Core\EventManager\Manager;
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
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Twig\TwigFilter;
use Twig\TwigFunction;

class ExportTypeSimple extends AbstractExportType
{
    protected $fullCollection = null;

    public function runExport(ExportJob $exportJob): Attachment
    {
        $this->getMemoryStorage()->set('exportJobId', $exportJob->get('id'));

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

        // Merge basic filters/functions with export filters/functions
        $filters = array_merge($this->getMetadata()->get(['twig', 'filters'], []), $this->getMetadata()->get(['app', 'twigFilters'], []));
        $functions = array_merge($this->getMetadata()->get(['twig', 'functions'], []), $this->getMetadata()->get(['app', 'twigFunctions'], []));

        foreach ($filters as $alias => $className) {
            $filter = $this->getContainer()->get($className);
            if ($filter instanceof \Atro\Core\Twig\AbstractTwigFilter) {
                $filter->setTemplateData($templateData);
                $twig->addFilter(new TwigFilter($alias, [$filter, 'filter']));
            }
        }

        foreach ($functions as $alias => $className) {
            $twigFunction = $this->getContainer()->get($className);
            if ($twigFunction instanceof \Atro\Core\Twig\AbstractTwigFunction && method_exists($twigFunction, 'run')) {
                $twigFunction->setTemplateData($templateData);
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

        $exportJob->set('count', $collection instanceof EntityCollection ? count($collection) : 0);

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

    protected function exportSql(ExportJob $exportJob): Attachment
    {
        if (!empty($this->data['feed']['separateJob'])) {
            $collection = $this->getCollection();
        } else {
            $collection = $this->getFullCollection();
        }

        $exportJob->set('count', $collection instanceof EntityCollection ? count($collection) : 0);

        $contents = $this->renderTemplateContents((string)$this->data['feed']['template'], ['entities' => $collection]);

        $contents = join("\n", array_map(function ($query) {
            return trim($query);
        }, \SqlFormatter::splitQuery($contents)));

        $repository = $this->getEntityManager()->getRepository('Attachment');

        // create attachment
        $attachment = $repository->get();
        $attachment->set('name', $this->getExportFileName('sql'));
        $attachment->set('role', 'Export');
        $attachment->set('relatedType', 'ExportJob');
        $attachment->set('relatedId', $this->data['exportJobId']);
        $attachment->set('storage', 'UploadDir');
        $attachment->set('storageFilePath', $this->createPath());

        $this->beforeStore($this, $attachment, 'sql');

        $fileName = $repository->getFilePath($attachment);

        $this->createDir($fileName);
        file_put_contents($fileName, $contents);

        $attachment->set('type', 'application/sql');
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

        $exportJob->set('count', $collection instanceof EntityCollection ? count($collection) : 0);

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

        $this->validateXml($fileName, $exportJob);

        return $attachment;
    }

    protected function validateXml($filename, ExportJob $exportJob)
    {
        $dom = new \DOMDocument();
        $dom->load($filename);
        libxml_use_internal_errors(true);
        $sxe = new \SimpleXMLElement($filename, 0, true);
        $schemaLocation = $sxe->attributes('xsi', true)->schemaLocation;
        $regex = '/https?\:\/\/[^\" ]+/i';
        preg_match($regex, (string)$schemaLocation, $matches);
        if (empty($matches[0])) return;

        $path = tempnam(sys_get_temp_dir(), "xsd");

        if ($this->downloadXsd($matches[0], $path) != "200") return;

        if (!$dom->schemaValidate($path)) {
            $logs = [];
            $validationFailed = false;

            foreach (libxml_get_errors() as $error) {
                $logs[] = $this->buildXmlLog($error);
                if ($error->level == LIBXML_ERR_ERROR || $error->level == LIBXML_ERR_FATAL) {
                    $validationFailed = true;
                }
            }
            libxml_clear_errors();

            $exportJob->set('stateMessage', $this->translate('xmlValidationFailed', 'messages', 'ExportJob') . "\n" . join("\n", $logs));
            if ($validationFailed) {
                $exportJob->set('state', 'Failed');
            }
        }
        unlink($path);
    }

    protected function downloadXsd($url, $path)
    {
        $options = array(
            CURLOPT_FILE           => fopen($path, 'w'),
            CURLOPT_TIMEOUT        => 28800,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL            => $url
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code;
    }

    function buildXmlLog($error): string
    {
        $log = "";
        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $log .= "Warning $error->code: ";
                break;
            case LIBXML_ERR_ERROR:
                $log .= "Error $error->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $log .= "Fatal Error $error->code: ";
                break;
        }
        $log .= trim($error->message) . " on line $error->line";

        return $log;
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

        $this->initZipArchive([$this->data['feed']['data']['configuration']]);

        $data = $this->createCacheFile();
        $exportJob->set('count', $data['count']);
        $exportJob->set('data', array_merge($exportJob->getData(), $data));

        $this->storeCsvFile($exportJob->getData(), $repository->getFilePath($attachment));

        $attachment->set('type', 'text/csv');
        $attachment->set('size', \filesize($repository->getFilePath($attachment)));

        $this->getEntityManager()->saveEntity($attachment);

        return $this->exportAsZip($attachment);
    }


    protected function canBuildZipArchive(array $configurations)
    {
        foreach ($configurations as $configuration) {
            foreach ($configuration as $field) {
                if ($field['zip']) return true;
            }
        }
        return false;
    }

    protected function initZipArchive(array $configurations)
    {
        if (!$this->canBuildZipArchive($configurations)) return false;
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

        $this->zipArchive = new \ZipArchive();
        if ($this->zipArchive->open($fileName, \ZipArchive::CREATE) !== true) {
            throw new Exception("cannot open archive $fileName\n");
        }
        $this->zipAttachment = $zipAttachment;
        return true;
    }

    protected function exportXlsx(ExportJob $exportJob): Attachment
    {
        $metadata = $this->getMetadata();

        if (!empty($this->data['feed']['sheets'])) {
            $sheets = $this->data['feed']['sheets'];
        } else {
            $sheets = [
                [
                    'name'          => 'Sheet',
                    'configuration' => $this->data['feed']['data']['configuration'],
                    'entity'        => $this->data['feed']['entity'],
                    'data'          => $this->data['feed']['data'],
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

        $this->initZipArchive(array_map(function ($sheet) {
            return $sheet['configuration'];
        }, $sheets));

        $this->beforeStore($this, $attachment, 'xlsx');

        $fileName = $repository->getFilePath($attachment);

        $this->createDir($fileName);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $count = 0;
        foreach ($sheets as $k => $sheet) {
            $this->data['feed']['data']['configuration'] = $sheet['configuration'];
            $this->data['feed']['entity'] = $sheet['entity'];
            $this->data['feed']['data']['entity'] = $sheet['entity'];
            $this->data['feed']['data']['whereScope'] = $sheet['entity'];
            $this->data['feed']['data']['where'] = $sheet['data']['where'] ?? [];
            if (count($sheets) > 1 && !empty($this->zipArchive) && $this->canBuildZipArchive([$sheet['configuration']])) {
                $base_dir = $sheet['name'] . '/';
                $this->data['zipPath'] = $base_dir;
                if (!$this->zipArchive->locateName($base_dir)) {
                    $this->zipArchive->addEmptyDir($base_dir);
                }
            }

            $this->convertor = $this->getDataConvertor();
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

            $entityDefs = $metadata->get(['entityDefs', $sheet['entity']]);
            $workSheet = $spreadsheet->getSheet($k);
            $startRow = 1;
            if ($sheet['data']['isFileHeaderRow']) {
                $startRow = 2;
            }

            // skip empty worksheets
            if ($startRow <= $workSheet->getHighestRow()) {
                foreach ($workSheet->getColumnIterator() as $configIndex => $column) {
                    $index = Coordinate::columnIndexFromString($configIndex) - 1;
                    if (!isset($sheet['configuration'][$index])) {
                        continue;
                    }

                    $sheetCol = $sheet['configuration'][$index];
                    $decimalMark = $sheetCol['decimalMark'];
                    $thousandSeparator = $sheetCol['thousandSeparator'];

                    switch ($sheetCol['type']) {
                        case 'Field':
                            $cellType = $entityDefs['fields'][$sheetCol['field']]['type'];
                            if (in_array($cellType, ['varchar', 'text', 'enum', 'multiEnum', 'extensibleMultiEnum', 'wysiwyg'])) {
                                foreach ($column->getCellIterator($startRow) as $cell) {
                                    $cell->setValueExplicit($cell->getValue(), DataType::TYPE_STRING2);
                                }
                            } else if ($cellType == 'float') {
                                foreach ($column->getCellIterator($startRow) as $cell) {
                                    $this->processXlsxNumericCell($cell, $decimalMark, $thousandSeparator);
                                }
                            } else if ($cellType == 'currency') {
                                foreach ($column->getCellIterator($startRow) as $cell) {
                                    // if currency field exported using value only mask
                                    if (preg_match("/^[\d\W]+$/", (string) $cell->getValue())) {
                                        $this->processXlsxNumericCell($cell, $decimalMark, $thousandSeparator);
                                    }
                                }
                            } else if ($cellType == 'int' && $thousandSeparator) {
                                foreach ($column->getCellIterator($startRow) as $cell) {
                                    if (is_string($cell->getValue())) {
                                        $this->processXlsxNumericCell($cell, $decimalMark, $thousandSeparator);
                                    }
                                }
                            }
                            break;
                        case 'Attribute':
                            if (in_array($sheetCol['attributeValue'], ['valueString', 'value'])) {
                                foreach ($column->getCellIterator($startRow) as $cell) {
                                    $cell->setValueExplicit($cell->getValue(), DataType::TYPE_STRING2);
                                }
                            } else if (in_array($sheetCol['attributeValue'], ['valueNumeric', 'valueFrom', 'valueTo'])) {
                                foreach ($column->getCellIterator($startRow) as $cell) {
                                    $this->processXlsxNumericCell($cell, $decimalMark, $thousandSeparator);
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }
            }

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

        return $this->exportAsZip($attachment);
    }

    private function processXlsxNumericCell(Cell $cell, $decimalMark, $thousandSeparator): void
    {
        $cellValue = (string) $cell->getValue();

        if ($thousandSeparator && str_contains($cellValue, $thousandSeparator)) {
            $cellValue = str_replace($thousandSeparator, "", $cellValue);
        }
        if ($decimalMark && str_contains($cellValue, $decimalMark)) {
            $cellValue = str_replace($decimalMark, ".", $cellValue);
        }

        if (is_numeric($cellValue)) {
            $cell->setValueExplicit($cellValue, DataType::TYPE_NUMERIC);
            if ($thousandSeparator && $cellValue != 0) {
                // hide decimal part for integers
                $format = filter_var($cellValue, FILTER_VALIDATE_INT) ? "#,##" : "#,##0." . str_repeat('0', strcspn(strrev($cellValue), '.'));
                $cell->getStyle()->getNumberFormat()->setFormatCode($format);
            }
        }
    }

    protected function exportAsZip(Attachment $attachment): Attachment
    {
        if (!empty($this->zipArchive)) {
            $repository = $this->getEntityManager()->getRepository('Attachment');
            $this->zipArchive->addFile($attachment->getFilePath(), $attachment->get('name'));
            $this->zipArchive->close();
            $this->zipAttachment->set('size', \filesize($repository->getFilePath($this->zipAttachment)));
            $this->getEntityManager()->saveEntity($this->zipAttachment);
            $this->getEntityManager()->removeEntity($attachment);
            return $this->zipAttachment;
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
                        'pos'    => $rowNumber * 1000 + $n,
                        'name'   => $colName
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
        $this->getEventManager()->dispatch('ExportTypeSimpleService', 'beforeStore', $event);
    }

    public function getUrlColumns(): array
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
        $data = $this->createCacheFile();
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

    protected function getEventManager(): Manager
    {
        return $this->getContainer()->get('eventManager');
    }
}
