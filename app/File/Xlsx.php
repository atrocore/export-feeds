<?php

declare(strict_types=1);

namespace Export\File;

use Treo\Entities\Attachment;
use Export\Core\FileStorage\Storages\AbstractStorage;

/**
 * Xlsx file creater
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
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
