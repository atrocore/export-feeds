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
