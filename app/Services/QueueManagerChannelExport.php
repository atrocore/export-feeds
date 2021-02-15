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

namespace Export\Services;

use Espo\ORM\EntityCollection;
use Export\File\Zip;
use Espo\Entities\Attachment;

/**
 * Class QueueManagerChannelExport
 */
class QueueManagerChannelExport extends QueueManagerExport
{
    /**
     * @inheritdoc
     */
    public function run(array $data = []): bool
    {
        // prepare name
        $name = $data['channel']['name'];

        if (!empty($atts = $this->getAttachments($data))) {
            $this->createZip($data['id'], $name, $atts);
        }

        return true;
    }

    /**
     * @param array $data
     *
     * @return EntityCollection|null
     */
    protected function getAttachments(array $data): ?EntityCollection
    {
        $attributeExportService = $this->getAttributeExportService();

        foreach ($data['feeds'] as $feed) {
            $feedData = ['feed' => $feed];

            if ($this->checkExistProductsAttributes($feedData, $attributeExportService)) {
                $this->setConfiguratorData($feedData, $attributeExportService->getAttributesConfig($feedData));
            }
            // get data
            $entityData = $this->getData($feedData);

            // create attachment
            $attachments[] = $this->createAttachment($feed, $entityData);
        }

        return (empty($attachments)) ? null : new EntityCollection($attachments);
    }

    /**
     * @param string           $id
     * @param string           $name
     * @param EntityCollection $attachments
     *
     * @return Attachment
     */
    protected function createZip(string $id, string $name, EntityCollection $attachments): Attachment
    {
        // create attachment
        $attachment = $this->getEntityManager()->getEntity('Attachment');
        $attachment->set('name', $name . '. ' . date("Y-m-d H:i:s") . '.zip');
        $attachment->set('role', 'Export Channel data as zip archive');
        $attachment->set('type', 'app/zip');
        $attachment->set('storage', 'ExportZip');
        $attachment->set('relatedId', $id);

        $this->getEntityManager()->saveEntity($attachment);

        // create zip file
        $zip = new \ZipArchive();
        $zip->open(sprintf(Zip::$filePathZip, $attachment->get('id')), \ZipArchive::CREATE);
        foreach ($attachments as $att) {
            $fileName = $this
                ->getEntityManager()
                ->getRepository('Attachment')
                ->getFilePath($att);
            if (file_exists($fileName)) {
                $zip->addFile($fileName, $att->get('name'));
            }
        }
        $zip->close();

        return $attachment;
    }

    /**
     * @param array           $data
     * @param AttributeExport $attributeExportService
     * @return bool
     */
    private function checkExistProductsAttributes(array $data, AttributeExport $attributeExportService): bool
    {
        return !$attributeExportService->configHasAttributes($this->getConfiguratorData($data))
            && $data['feed']['data']['entity'] === "Product";
    }
}
