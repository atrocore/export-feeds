<?php

declare(strict_types=1);

namespace Export\Core\FileStorage\Storages;

use Treo\Core\FileStorage\Storages\UploadDir;

/**
 * AbstractStorage FileStorage
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
abstract class AbstractStorage extends UploadDir
{
    const EXPORT_DIR = 'data/export-data';
}
