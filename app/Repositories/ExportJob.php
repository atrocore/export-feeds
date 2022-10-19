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
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

declare(strict_types=1);

namespace Export\Repositories;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Templates\Repositories\Base;
use Espo\ORM\Entity;

class ExportJob extends Base
{
    protected const JOBS_MAX = 200;

    public function getExportJob(string $exportJobId): ?Entity
    {
        return $this->getEntityManager()->getRepository('QueueItem')->where(['data*' => '%"exportJobId":"' . $exportJobId . '"%'])->findOne();
    }

    protected function init()
    {
        parent::init();

        $this->addDependency('language');
        $this->addDependency('queueManager');
    }

    protected function beforeSave(Entity $entity, array $options = [])
    {
        if ($entity->isNew()) {
            $last = $this->select(['sortOrder'])->where(['exportFeedId' => $entity->get('exportFeedId')])->order('sortOrder', 'DESC')->findOne();
            $entity->set('sortOrder', empty($last) ? 0 : $last->get('sortOrder') + 10);
        } else {
            if ($entity->isAttributeChanged('state')) {
                if ($entity->get('state') === 'Canceled' && !in_array($entity->getFetched('state'), ['Pending', 'Running'])) {
                    throw new BadRequest($this->getInjection('language')->translate('wrongJobState', 'exceptions', 'ExportJob'));
                }
                if ($entity->get('state') === 'Pending') {
                    if ($entity->getFetched('state') === 'Running') {
                        throw new BadRequest($this->getInjection('language')->translate('wrongJobState', 'exceptions', 'ExportJob'));
                    }
                    $qmJob = $this->getExportJob($entity->get('id'));
                    if (empty($qmJob)) {
                        throw new BadRequest($this->getInjection('language')->translate('notExecutableJob', 'exceptions', 'ExportJob'));
                    }
                }
            }
        }

        parent::beforeSave($entity, $options);
    }

    protected function afterSave(Entity $entity, array $options = [])
    {
        parent::afterSave($entity, $options);

        if ($entity->isAttributeChanged('state')) {
            $qmJob = $this->getExportJob($entity->get('id'));
            if (!empty($qmJob)) {
                if ($entity->get('state') === 'Pending') {
                    $this->toPendingQmJob($qmJob);
                }
                if ($entity->get('state') === 'Canceled') {
                    $this->cancelQmJob($qmJob);
                }
            }
        }

        if (!empty($feed = $entity->get('exportFeed'))) {
            $jobs = $this
                ->where([
                    'exportFeedId' => $feed->get('id'),
                    'state'        => ['Success', 'Failed', 'Canceled']
                ])
                ->order('createdAt')
                ->limit(self::JOBS_MAX, 100)
                ->find();
            foreach ($jobs as $job) {
                $this->getEntityManager()->removeEntity($job);
            }
        }
    }

    protected function afterRemove(Entity $entity, array $options = [])
    {
        if (!empty($file = $entity->get('file'))) {
            $this->getEntityManager()->removeEntity($file);
        }

        $data = $entity->getData();
        if (isset($data['fullFileName']) && file_exists($data['fullFileName'])) {
            unlink($data['fullFileName']);
        }

        $qmJob = $this->getExportJob($entity->get('id'));
        if (!empty($qmJob)) {
            $this->cancelQmJob($qmJob);
            $this->getEntityManager()->removeEntity($qmJob);
        }

        parent::afterRemove($entity, $options);
    }

    protected function toPendingQmJob(Entity $qmJob): void
    {
        $this->getInjection('queueManager')->tryAgain($qmJob->get('id'));
    }

    protected function cancelQmJob(Entity $qmJob): void
    {
        if (in_array($qmJob->get('status'), ['Pending', 'Running'])) {
            $qmJob->set('status', 'Canceled');
            $this->getEntityManager()->saveEntity($qmJob);
        }
    }
}
