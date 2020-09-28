<?php

declare(strict_types=1);

namespace Export\Services;

use Espo\ORM\EntityCollection;
use Export\File\Zip;
use Treo\Entities\Attachment;

/**
 * Class QueueManagerChannelExport
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
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
