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

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Json;
use Espo\Entities\Attachment;
use Espo\ORM\EntityCollection;
use Export\Entities\ExportJob;
use Export\TwigFilter\AbstractTwigFilter;
use Export\TwigFunction\AbstractTwigFunction;
use Twig\TwigFilter;
use Twig\TwigFunction;

class ExportTypeSimple extends AbstractExportType
{
    public function runExport(ExportJob $exportJob): Attachment
    {
        $attachmentCreatorName = 'export' . ucfirst($this->data['feed']['fileType']);
        if (!method_exists($this, $attachmentCreatorName)) {
            throw new Error('Unsupported file type.');
        }

        return $this->$attachmentCreatorName($exportJob);
    }

    protected function renderTemplateContents(EntityCollection $collection): string
    {
        $twig = new \Twig\Environment(new \Twig\Loader\ArrayLoader(['template' => (string)$this->data['feed']['template']]));
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

        return $twig->render('template', [
            'entities' => $collection,
            'config'   => $this->getConfig()->getData(),
            'feedData' => $this->data['feed'],
        ]);
    }

    protected function exportJson(ExportJob $exportJob): Attachment
    {
        $collection = new EntityCollection();
        while (!empty($v = $this->getCollection())) {
            foreach ($v as $entity) {
                $collection->append($entity);
            }
        }

        $exportJob->set('count', count($collection));

        $contents = $this->renderTemplateContents($collection);

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
        $collection = new EntityCollection();
        while (!empty($v = $this->getCollection())) {
            foreach ($v as $entity) {
                $collection->append($entity);
            }
        }

        $exportJob->set('count', count($collection));

        $contents = $this->renderTemplateContents($collection);

        $repository = $this->getEntityManager()->getRepository('Attachment');

        // create attachment
        $attachment = $repository->get();
        $attachment->set('name', $this->getExportFileName('xml'));
        $attachment->set('role', 'Export');
        $attachment->set('relatedType', 'ExportJob');
        $attachment->set('relatedId', $this->data['exportJobId']);
        $attachment->set('storage', 'UploadDir');
        $attachment->set('storageFilePath', $this->createPath());

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
        $this->createCacheFile($exportJob);

        $repository = $this->getEntityManager()->getRepository('Attachment');

        // create attachment
        $attachment = $repository->get();
        $attachment->set('name', $this->getExportFileName('csv'));
        $attachment->set('role', 'Export');
        $attachment->set('relatedType', 'ExportJob');
        $attachment->set('relatedId', $this->data['exportJobId']);
        $attachment->set('storage', 'UploadDir');
        $attachment->set('storageFilePath', $this->createPath());

        $this->storeCsvFile($exportJob->getData(), $repository->getFilePath($attachment));

        $attachment->set('type', 'text/csv');
        $attachment->set('size', \filesize($repository->getFilePath($attachment)));

        $this->getEntityManager()->saveEntity($attachment);

        return $attachment;
    }

    protected function exportXlsx(ExportJob $exportJob): Attachment
    {
        $this->createCacheFile($exportJob);

        $repository = $this->getEntityManager()->getRepository('Attachment');

        // create attachment
        $attachment = $repository->get();
        $attachment->set('name', $this->getExportFileName('xlsx'));
        $attachment->set('role', 'Export');
        $attachment->set('relatedType', 'ExportJob');
        $attachment->set('relatedId', $this->data['exportJobId']);
        $attachment->set('storage', 'UploadDir');
        $attachment->set('storageFilePath', $this->createPath());

        $this->storeXlsxFile($exportJob->getData(), $repository->getFilePath($attachment));

        $attachment->set('type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $attachment->set('size', \filesize($repository->getFilePath($attachment)));

        $this->getEntityManager()->saveEntity($attachment);

        return $attachment;
    }

    protected function prepareColumns(array $data): array
    {
        $columns = [];

        $cacheFile = fopen($data['fullFileName'], "r");
        while (($json = fgets($cacheFile)) !== false) {
            if (empty($json)) {
                continue;
            }

            foreach ($data['configuration'] as $rowNumber => $row) {
                $row['convertCollectionToString'] = false;
                $row['convertRelationsToString'] = false;

                $converted = $this->convertor->convert(Json::decode($json, true), $row);
                $n = 0;
                foreach ($converted as $colName => $value) {
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

        // prepare settings
        $delimiter = $this->data['feed']['csvFieldDelimiter'];
        if ($delimiter === '\t') {
            $delimiter = "\t";
        }

        $enclosure = ($this->data['feed']['csvTextQualifier'] == 'doubleQuote') ? '"' : "'";

        $fp = fopen($fileName, "w");

        // prepare header
        if ($this->data['feed']['isFileHeaderRow']) {
            fputcsv($fp, array_column($columns, 'name'), $delimiter, $enclosure);
        }

        $cacheFile = fopen($data['fullFileName'], "r");
        while (($json = fgets($cacheFile)) !== false) {
            if (empty($json)) {
                continue;
            }

            $rowData = [];
            foreach ($data['configuration'] as $row) {
                $rowData[] = $this->convertor->convert(Json::decode($json, true), $row, true);
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
    }

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
}
