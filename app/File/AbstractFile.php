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

use Treo\Core\FileStorage\Manager as FileManager;
use Treo\Entities\Attachment;
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
     * @return AbstractType
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
     * @return AbstractType
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
