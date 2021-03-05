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

namespace Export\Repositories;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Templates\Repositories\Base;
use Espo\ORM\Entity;

/**
 * Class ExportResult
 */
class ExportResult extends Base
{
    public function getExportResultJob(string $exportResultId): ?Entity
    {
        return $this->getEntityManager()->getRepository('QueueItem')->where(['data*' => '%"exportResultId":"' . $exportResultId . '"%'])->findOne();
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        parent::init();

        $this->addDependency('language');
    }

    /**
     * @param Entity $entity
     * @param array  $options
     */
    protected function beforeSave(Entity $entity, array $options = [])
    {
        if ($entity->isNew() && empty($entity->get('name'))) {
            $name = (new \DateTime())->format('Y-m-d H:i:s');
            if (!empty($count = $this->where(['name*' => "$name%"])->count())) {
                $name .= " ($count)";
            }
            $entity->set('name', $name);
        }

        parent::beforeSave($entity, $options);
    }

    /**
     * @param Entity $entity
     * @param array  $options
     *
     * @throws BadRequest
     */
    protected function beforeRemove(Entity $entity, array $options = [])
    {
        if (!empty($job = $this->getExportResultJob($entity->get('id')))) {
            if ($job->get('status') == 'Running') {
                throw new BadRequest($this->getInjection('language')->translate('exportIsRunning', 'exceptions', 'ExportResult'));
            }
        }

        parent::beforeRemove($entity, $options);
    }

    /**
     * @param Entity $entity
     * @param array  $options
     */
    protected function afterRemove(Entity $entity, array $options = [])
    {
        if (!empty($file = $entity->get('file'))) {
            $this->getEntityManager()->removeEntity($file);
        }

        $this->deleteQueueItem((string)$entity->get('id'));

        parent::afterRemove($entity, $options);
    }

    /**
     * @param string $id
     */
    protected function deleteQueueItem(string $id): void
    {
        $this
            ->getEntityManager()
            ->getPDO()
            ->exec("UPDATE queue_item SET deleted=1 WHERE data LIKE '%\"exportResultId\":\"$id\"%'");
    }
}
