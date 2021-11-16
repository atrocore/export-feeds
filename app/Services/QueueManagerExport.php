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
use Espo\Services\QueueManagerBase;

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
        $exportJob = $this->getEntityManager()->getEntity('ExportJob', $data['exportJobId']);
        if (empty($exportJob)) {
            return false;
        }
        $exportJob->set('state', 'Running');
        $this->getEntityManager()->saveEntity($exportJob);

        try {
            /** @var string $feedTypeClass */
            $feedTypeClass = $this->getMetadata()->get(['app', 'export', 'type', $data['feed']['type']], '');

            if (empty($feedTypeClass) || !is_a($feedTypeClass, AbstractType::class, true)) {
                throw new Error($this->getContainer()->get('language')->translate('wrongExportFeedType', 'exceptions', 'ExportFeed'));
            }

            $attachment = (new $feedTypeClass($this->getContainer(), $data))->export();
            $exportJob->set('end', (new \DateTime())->format('Y-m-d H:i:s'));
            $exportJob->set('state', 'Done');
            $exportJob->set('fileId', $attachment->get('id'));
            $this->getEntityManager()->saveEntity($exportJob);
        } catch (\Throwable $e) {
            $exportJob->set('end', (new \DateTime())->format('Y-m-d H:i:s'));
            $exportJob->set('state', 'Failed');
            $exportJob->set('stateMessage', $e->getMessage());
            $this->getEntityManager()->saveEntity($exportJob);
            $GLOBALS['log']->error('Export Error: ' . $e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getNotificationMessage(Entity $queueItem): string
    {
        $message = parent::getNotificationMessage($queueItem);

        if ($queueItem->get('status') === 'Success') {
            try {
                $exportJob = $this->getEntityManager()->getEntity('ExportJob', $queueItem->get('data')->exportJobId);
            } catch (\Throwable $e) {
                $GLOBALS['log']->error('Export Notification Error: ' . $e->getMessage());
                return $message;
            }

            $message .= ' ' . sprintf($this->translate('exportDownloadNotification', 'labels', 'ExportJob'), $exportJob->get('fileId'));
        }

        return $message;
    }

    /**
     * @return Metadata
     */
    protected function getMetadata(): Metadata
    {
        return $this->getContainer()->get('metadata');
    }
}
