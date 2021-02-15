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

use Treo\Core\FileStorage\Manager as FileManager;
use Espo\Entities\Attachment;
use Espo\ORM\EntityManager;
use Export\Core\FileStorage\Storages\AbstractStorage;

/**
 * AbstractFile class for file creaters
 */
abstract class AbstractFile
{
    /**
     * @var array
     */
    protected $feed;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * Create file
     *
     * @return Attachment
     */
    abstract public function create(): Attachment;

    /**
     * Update file
     *
     * @param string $attachmentId
     * @param int    $offset
     *
     * @return Attachment
     */
    abstract public function update(string $attachmentId, int $offset = 0): Attachment;

    /**
     * AbstractStorage constructor.
     */
    public function __construct()
    {
        // create dir
        if (!file_exists(AbstractStorage::EXPORT_DIR)) {
            mkdir(AbstractStorage::EXPORT_DIR);
        }
    }

    /**
     * Set feed
     *
     * @param array $feed
     *
     * @return AbstractFile
     */
    public function setFeed(array $feed): AbstractFile
    {
        $this->feed = $feed;

        return $this;
    }

    /**
     * Set entity manager
     *
     * @param EntityManager $entityManager
     *
     * @return AbstractFile
     */
    public function setEntityManager(EntityManager $entityManager): AbstractFile
    {
        $this->entityManager = $entityManager;

        return $this;
    }

    /**
     * Set data for export
     *
     * @param array $data
     *
     * @return AbstractFile
     */
    public function setData(array $data): AbstractFile
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set file manager
     *
     * @param FileManager $fileManager
     *
     * @return AbstractFile
     */
    public function setFileManager(FileManager $fileManager): AbstractFile
    {
        $this->fileManager = $fileManager;

        return $this;
    }

    /**
     * Get feed
     *
     * @return array
     */
    protected function getFeed(): array
    {
        return $this->feed;
    }

    /**
     * Get entity manager
     *
     * @return EntityManager
     */
    protected function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    /**
     * Get data for export
     *
     * @return array
     */
    protected function getData(): array
    {
        return $this->data;
    }

    /**
     * Get file manager
     *
     * @return FileManager
     */
    protected function getFileManager(): FileManager
    {
        return $this->fileManager;
    }
}
