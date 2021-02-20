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

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;
use Export\ExportType\AbstractType;
use Treo\Services\QueueManagerBase;

/**
 * Class QueueManagerExport
 */
class QueueManagerExport extends QueueManagerBase
{
    /**
     * @param array $data
     *
     * @return bool
     * @throws Error
     */
    public function run(array $data = []): bool
    {
        $exportResult = $this->getEntityManager()->getEntity('ExportResult', $data['exportResultId']);
        if (empty($exportResult)) {
            return false;
        }
        $exportResult->set('state', 'Running');
        $this->getEntityManager()->saveEntity($exportResult);

        try {
            /** @var string $feedTypeClass */
            $feedTypeClass = $this->getMetadata()->get(['app', 'export', 'type', $data['feed']['type']], '');

            if (empty($feedTypeClass) || !is_a($feedTypeClass, AbstractType::class, true)) {
                throw new Error($this->getContainer()->get('language')->translate('wrongExportFeedType', 'exceptions', 'ExportFeed'));
            }

            $attachment = (new $feedTypeClass($this->getContainer(), $data))->export();
            $exportResult->set('end', (new \DateTime())->format('Y-m-d H:i:s'));
            $exportResult->set('state', 'Done');
            $exportResult->set('fileId', $attachment->get('id'));
            $this->getEntityManager()->saveEntity($exportResult);
        } catch (\Throwable $e) {
            $exportResult->set('end', (new \DateTime())->format('Y-m-d H:i:s'));
            $exportResult->set('state', 'Failed');
            $this->getEntityManager()->saveEntity($exportResult);
            $GLOBALS['log']->error('Export Error: ' . $e->getMessage());

            return false;
        }

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
                ->where(['relatedType' => 'ExportResult', 'relatedId' => $entity->get('data')->id])
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
     * @return Metadata
     */
    protected function getMetadata(): Metadata
    {
        return $this->getContainer()->get('metadata');
    }
}
