<?php

declare(strict_types=1);

namespace Export\Core\FileStorage\Storages;

use Export\File\Xlsx;
use Treo\Entities\Attachment;

/**
 * ExportFeedXlsx Storage
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ExportFeedXlsx extends AbstractStorage
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
        return sprintf(Xlsx::$filePathXlsx, $attachment->get('id'));
    }
}
