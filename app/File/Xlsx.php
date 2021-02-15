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

namespace Export\File;

use Espo\Entities\Attachment;
use Export\Core\FileStorage\Storages\AbstractStorage;

/**
 * Xlsx file creater
 */
class Xlsx extends Csv
{
    /**
     * @var string
     */
    public static $filePathXlsx = AbstractStorage::EXPORT_DIR . '/%s.xlsx';

    /**
     * Create file
     *
     * @return Attachment
     */
    public function create(): Attachment
    {
        // create attachment
        $attachment = $this->getEntityManager()->getEntity('Attachment');
        $attachment->set('name', $this->getFeed()['name'] . '. ' . date('Y-m-d H:i:s') . '.xlsx');
        $attachment->set('role', 'Export File by export feed');
        $attachment->set('type', 'app/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $attachment->set('storage', 'ExportFeedXlsx');
        $attachment->set('storageFilePath', AbstractStorage::EXPORT_DIR);
        
        $this->getEntityManager()->saveEntity($attachment);

        // store file
        $this->storeFile($attachment);

        return $attachment;
    }

    /**
     * Store file
     *
     * @param Attachment $attachment
     * @param int        $offset
     *
     * @return void
     */
    protected function storeFile(Attachment $attachment, int $offset = 0): void
    {
        // prepare settings
        $this->getFeed()['csvFieldDelimiter'] = ";";
        $this->getFeed()['csvTextQualifier'] = 'doubleQuote';

        // create csv
        parent::storeFile($attachment, $offset);

        // create xlsx
        $this->createXlsx($attachment);
    }

    /**
     * @param Attachment $attachment
     */
    protected function createXlsx(Attachment $attachment): void
    {
        // prepare csv path
        $csvPath = sprintf(Csv::$filePathCsv, $attachment->get('id'));

        if (file_exists($csvPath)) {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();

            // set CSV parsing options
            $reader->setDelimiter(";");
            $reader->setEnclosure('"');
            $reader->setSheetIndex(0);

            // load a CSV file and save as a XLS
            $spreadsheet = $reader->load($csvPath);
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
            $writer->save(sprintf(Xlsx::$filePathXlsx, $attachment->get('id')));

            // delete csv file
            unlink($csvPath);
        }
    }
}
