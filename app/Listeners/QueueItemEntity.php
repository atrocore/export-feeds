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

namespace Export\Listeners;

use Espo\ORM\Entity;
use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class QueueItemEntity
 */
class QueueItemEntity extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        // prepare entity
        $entity = $event->getArgument('entity');

        if (!empty($entity->get('data')->exportJobId)) {
            $this->updateExportJob($entity);
        }
    }

    /**
     * @param Event $event
     */
    public function afterRemove(Event $event)
    {
        // prepare entity
        $entity = $event->getArgument('entity');

        if (!empty($entity->get('data')->exportJobId)) {
            $this->removeExportJob($entity);
        }
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function updateExportJob(Entity $entity): bool
    {
        $exportJob = $this->getEntityManager()->getEntity('ExportJob', $entity->get('data')->exportJobId);
        if (empty($exportJob)) {
            return false;
        }

        if ($entity->get('status') === 'Canceled') {
            $this->getEntityManager()->removeEntity($exportJob);
            return true;
        }

        $exportJob->set('state', $entity->get('status'));
        $this->getEntityManager()->saveEntity($exportJob);
        return true;
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function removeExportJob(Entity $entity): bool
    {
        $exportJob = $this->getEntityManager()->getEntity('ExportJob', $entity->get('data')->exportJobId);

        if (empty($exportJob)) {
            return false;
        }

        if ($entity->get('status') == 'Pending') {
            $this->getEntityManager()->removeEntity($exportJob);
        }

        return true;
    }
}
