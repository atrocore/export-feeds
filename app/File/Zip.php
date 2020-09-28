<?php

declare(strict_types=1);

namespace Export\File;

use Treo\Entities\Attachment;
use Export\Core\FileStorage\Storages\AbstractStorage;

/**
 * Zip file creater
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Zip extends AbstractFile
{
    /**
     * @var string
     */
    public static $filePathZip = AbstractStorage::EXPORT_DIR . '/%s.zip';

    /**
     * Create file
     *
     * @return Attachment
     */
    public function create(): Attachment
    {
        // create attachment
        $attachment = $this->getEntityManager()->getEntity('Attachment');
        $attachment->set('name', $this->getFeed()['name'] . '. ' . date('Y-m-d H:i:s') . '.zip');
        $attachment->set('role', 'Export zip file by export feed');
        $attachment->set('type', 'app/zip');
        $attachment->set('storage', 'ExportZip');
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
        return $this->create();
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
        if (!empty($files = $this->getData()['files'])) {
            // prepare name
            $name = sprintf(self::$filePathZip, $attachment->get('id'));

            // create zip file
            $zip = new \ZipArchive();
            $zip->open($name, \ZipArchive::CREATE);
            foreach ($files as $row) {
                if (!empty($row['filename']) && !empty($row['localname'])) {
                    $zip->addFile($row['filename'], $row['localname']);
                }
            }
            $zip->close();
        }
    }
}
