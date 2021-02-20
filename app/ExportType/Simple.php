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

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Json;
use Espo\Entities\Attachment;
use Export\ExportData\Record;
use Treo\Core\FilePathBuilder;

/**
 * Type Simple
 */
class Simple extends AbstractType
{
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
     */
    protected function getData(): array
    {
        // prepare result
        $result = [];

        // prepare export feed data
        $data = $this->getFeedData();

        // entities
        $entities = $this->getEntities();

        // get prepare data class
        $dataPrepare = $this->getPrepareDataClass($data['entity']);

        if (!empty($entities)) {
            // prepare result
            foreach ($entities as $entity) {
                $result[$entity->get('id')] = [];

                foreach ($data['configuration'] as $row) {
                    $result[$entity->get('id')]
                        = array_merge($result[$entity->get('id')], $dataPrepare->prepare($entity, $row, $data['delimiter']));
                }
            }
            $result = array_values($result);
        }

        if (empty($result)) {
            foreach ($data['configuration'] as $row) {
                $result[0][$row['column']] = '';
            }
        }

        return $result;
    }

    /**
     * @return mixed
     */
    protected function getEntities()
    {
        $data = $this->getFeedData();

        $selectParams = $this
            ->getSelectManager($this->data['feed']['data']['entity'])
            ->getSelectParams(isset($data['where']) ? ['where' => $data['where']] : [], true, true);

        return $this
            ->getEntityManager()
            ->getRepository($data['entity'])
            ->find($selectParams);
    }

    /**
     * @param string $entityName
     *
     * @return Record
     */
    protected function getPrepareDataClass(string $entityName): Record
    {
        $prepareDataClassName = "Export\\ExportData\\" . $entityName;

        if (!class_exists($prepareDataClassName)) {
            $prepareDataClassName = "Export\\ExportData\\Record";
        }

        return (new $prepareDataClassName())->setContainer($this->container);
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
        foreach ($data as $row) {
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
            fputcsv($fp, array_keys($data[0]), $delimiter, $enclosure);
        }

        // prepare rows
        foreach ($rows as $item) {
            fputcsv($fp, $item, $delimiter, $enclosure);
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
}
