<?php

declare(strict_types=1);

namespace Export\File;

use Treo\Entities\Attachment;
use Export\Core\FileStorage\Storages\AbstractStorage;

/**
 * Csv file creater
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Csv extends AbstractFile
{
    /**
     * @var string
     */
    public static $filePathCsv = AbstractStorage::EXPORT_DIR . '/%s.csv';

    /**
     * Create file
     *
     * @return Attachment
     */
    public function create(): Attachment
    {
        // create attachment
        $attachment = $this->getEntityManager()->getEntity('Attachment');
        $attachment->set('name', $this->getFeed()['name'] . '. ' . date('Y-m-d H:i:s') . '.csv');
        $attachment->set('role', 'Export File by export feed');
        $attachment->set('type', 'text/csv');
        $attachment->set('storage', 'ExportFeedCsv');
        $attachment->set('storageFilePath', AbstractStorage::EXPORT_DIR);
        $this->getEntityManager()->saveEntity($attachment);

        // store file
        $this->storeFile($attachment);

        return $attachment;
    }

    /**
     * Update file
     *
     * @param string $attachmentId
     * @param int    $offset
     *
     * @return Attachment
     */
    public function update(string $attachmentId, int $offset = 0): Attachment
    {
        // get attachment
        $attachment = $this->getEntityManager()->getEntity('Attachment', $attachmentId);

        // store file
        $this->storeFile($attachment, $offset);

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
        if (!empty($this->getData())) {
            // prepare path
            $path = sprintf(self::$filePathCsv, $attachment->get('id'));

            // prepare settings
            $delimiter = $this->getFeed()['csvFieldDelimiter'];
            $enclosure = ($this->getFeed()['csvTextQualifier'] == 'doubleQuote') ? '"' : "'";

            /**
             * Prepare data
             */
            $data = [];
            if (!empty($offset)) {
                if (($handle = fopen($path, "r")) !== false) {
                    while (($row = fgetcsv($handle, 0, $delimiter, $enclosure)) !== false) {
                        $data[] = $row;
                    }
                    fclose($handle);
                }
            }
            foreach ($this->getData() as $row) {
                foreach ($row as $key => $field) {
                    if (is_array($field)) {
                        $row[$key] = '[' . implode(",", $field) . ']';
                    }
                }
                $data[] = array_values($row);
            }

            // open file
            $fp = fopen(sprintf(self::$filePathCsv, $attachment->get('id')), "w");

            // prepare header
            if ($this->getFeed()['isFileHeaderRow'] && $offset == 0) {
                fputcsv($fp, array_keys($this->getData()[0]), $delimiter, $enclosure);
            }

            // prepare rows
            foreach ($data as $item) {
                fputcsv($fp, $item, $delimiter, $enclosure);
            }

            // rewind
            rewind($fp);

            // close file
            fclose($fp);
        }
    }
}
