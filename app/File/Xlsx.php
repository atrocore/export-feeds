<?php
/*
 * This file is part of premium software, which is NOT free.
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This Software is the property of AtroCore UG (haftungsbeschränkt) and is
 * protected by copyright law - it is NOT Freeware and can be used only in one
 * project under a proprietary license, which is delivered along with this program.
 * If not, see <https://atropim.com/eula> or <https://atrodam.com/eula>.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
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
