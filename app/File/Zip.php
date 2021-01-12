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
 * Zip file creater
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
