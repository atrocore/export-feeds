<?php

declare(strict_types=1);

namespace Export\Core\FileStorage\Storages;

use Export\File\Csv;
use Treo\Entities\Attachment;

/**
 * ExportFeedCsv Storage
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ExportFeedCsv extends AbstractStorage
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
        return sprintf(Csv::$filePathCsv, $attachment->get('id'));
    }
}
