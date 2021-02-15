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

use Espo\ORM\Entity;
use Export\ExportType\ExportTypeFactory;
use Espo\Entities\Attachment;

/**
 * Class QueueManagerExport
 */
class QueueManagerExport extends \Treo\Services\QueueManagerBase
{
    /**
     * @inheritdoc
     */
    public function run(array $data = []): bool
    {
        $attributeExportService = $this->getAttributeExportService();

        if ($this->checkExistProductsAttributes($data, $attributeExportService)) {
            $this->setConfiguratorData($data, $attributeExportService->getAttributesConfig($data));
        }

        // get data
        $entityData = $this->getData($data);

        // create attachment
        $this->createAttachment($data['feed'], $entityData, $data['id']);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getSuccessStatusActions(Entity $entity): array
    {
        // prepare actions
        $actions = parent::getSuccessStatusActions($entity);

        // push download action
        if (isset($entity->get('data')->id)) {
            // get attachment
            $attachment = $this
                ->getEntityManager()
                ->getRepository('Attachment')
                ->select(['id'])
                ->where(['relatedId' => $entity->get('data')->id])
                ->findOne();

            if (!empty($attachment)) {
                $actions[] = [
                    'type' => 'download',
                    'data' => ['attachmentId' => $attachment->get('id')],
                ];
            }
        }

        return $actions;
    }

    /**
     * @return ExportTypeFactory
     */
    protected function getExportTypeFactory(): ExportTypeFactory
    {
        return $this->getContainer()->get('exportTypeFactory');
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function getData(array $data): array
    {
        return $this
            ->getExportTypeFactory()
            ->create($data['feed']['type'])
            ->setFeed($data['feed'])
            ->getData();
    }

    /**
     * @param array       $feed
     * @param array       $data
     * @param string|null $id
     *
     * @return Attachment
     */
    protected function createAttachment(array $feed, array $data, string $id = null): Attachment
    {
        // get config data
        $config = $this->getExportTypeFactory()->getExportConfig();

        // create
        $attachment = (new $config['fileType'][$feed['fileType']]())
            ->setFileManager($this->getContainer()->get('fileStorageManager'))
            ->setEntityManager($this->getEntityManager())
            ->setFeed($feed)
            ->setData($data)
            ->create();

        if (!empty($id)) {
            $attachment->set('relatedId', $id);
            $this->getEntityManager()->saveEntity($attachment, ['skipAll']);
        }

        return $attachment;
    }

    /**
     * @return AttributeExport
     */
    protected function getAttributeExportService(): AttributeExport
    {
        return $this->getContainer()->get("serviceFactory")->create("AttributeExport");
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getConfiguratorData(array $data): array
    {
        if (isset($data['feed']['data']['configuration'])) {
            return $data['feed']['data']['configuration'];
        }

        return [];
    }

    /**
     * @param array $data
     * @param array $newData
     * @return QueueManagerExport
     */
    protected function setConfiguratorData(array &$data, array $newData): QueueManagerExport
    {
        if (isset($data['feed']['data']['configuration'])) {
            $data['feed']['data']['configuration'] = array_merge($data['feed']['data']['configuration'], $newData);
        }

        return $this;
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
