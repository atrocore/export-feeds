<?php

declare(strict_types=1);

namespace Export\Core\FileStorage\Storages;

use Treo\Entities\Attachment;
use Export\File\Zip;

/**
 * ExportZip Storage
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ExportZip extends AbstractStorage
{
    /**
     * Get file path
     *
     * @param Attachment $attachment
     *
     * @return string
     */
    protected function getFilePath(Attachment $attachment): string
    {
        return sprintf(Zip::$filePathZip, $attachment->get('id'));
    }
}
