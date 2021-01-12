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
