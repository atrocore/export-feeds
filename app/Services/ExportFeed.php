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

use Espo\Core\Exceptions;
use Espo\Core\Templates\Services\Base;
use Espo\Core\Utils\Util;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Export\ExportType\AbstractType;
use Export\ExportType\Simple;

/**
 * ExportFeed service
 */
class ExportFeed extends Base
{
    /**
     * Export file
     *
     * @param \stdClass $requestData
     *
     * @return bool
     * @throws Exceptions\NotFound
     */
    public function exportFile(\stdClass $requestData): bool
    {
        if (empty($exportFeed = $this->getEntityManager()->getEntity('ExportFeed', $requestData->id))) {
            throw new Exceptions\NotFound();
        }

        $data = [
            'id'   => Util::generateId(),
            'feed' => $exportFeed->toArray()
        ];

        if (!empty($requestData->ignoreFilter)) {
            $data['feed']['data']->where = [];
        }

        if (!empty($requestData->entityFilterData)) {
            if (!empty($requestData->entityFilterData->byWhere)) {
                $data['feed']['data']->where = array_merge($data['feed']['data']->where, $requestData->entityFilterData->where);
            } else {
                $data['feed']['data']->where[] = [
                    'type'      => 'in',
                    'attribute' => 'id',
                    'value'     => $requestData->entityFilterData->ids
                ];
            }

            $this->pushExport($data);
            return true;
        }

        if (!empty($requestData->exportByChannelId)) {
            $data['exportByChannelId'] = $requestData->exportByChannelId;
            $this->pushExport($data);
        } else {
            $channelsIds = array_column($exportFeed->get('channels')->toArray(), 'id');
            if (empty($channelsIds)) {
                $this->pushExport($data);
            } else {
                foreach ($channelsIds as $channelId) {
                    $data['exportByChannelId'] = $channelId;
                    $this->pushExport($data);
                }
            }
        }

        return true;
    }

    /**
     * Export all channel feeds
     *
     * @param string $channelId
     *
     * @return bool
     */
    public function exportChannel(string $channelId): bool
    {
        // prepare result
        $result = false;

        if (!empty($channel = $this->getChannel($channelId)) && !empty($feeds = $this->getChannelFeeds($channel))) {
            foreach ($feeds as $feed) {
                $requestData = new \stdClass();
                $requestData->id = $feed->get('id');
                $requestData->exportByChannelId = $channelId;

                try {
                    $this->exportFile($requestData);
                } catch (\Throwable $e) {
                    $GLOBALS['log']->error('Export Error: ' . $e->getMessage());
                }
            }
            $result = true;
        }

        return $result;
    }

    /**
     * @param string $scope
     *
     * @return array
     */
    public function getAllFieldsConfigurator(string $scope): array
    {
        return Simple::getAllFieldsConfiguration($scope, $this->getMetadata(), $this->getInjection('language'));
    }

    /**
     * Init
     */
    protected function init()
    {
        parent::init();

        $this->addDependency('queueManager');
        $this->addDependency('language');
        $this->addDependency('user');
    }


    /**
     * Translate field
     *
     * @param string $key
     * @param string $tab
     *
     * @return string
     */
    protected function translate(string $key, string $tab = 'additionalTranslates'): string
    {
        return $this->getInjection('language')->translate($key, $tab, 'ExportFeed');
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function pushExport(array $data): bool
    {
        /** @var User $user */
        $user = $this->getInjection('user');

        $exportResult = $this->getEntityManager()->getEntity('ExportResult');
        $exportResult->set('exportFeedId', $data['feed']['id']);
        $exportResult->set('start', (new \DateTime())->format('Y-m-d H:i:s'));
        $exportResult->set('ownerUserId', $user->get('id'));
        $exportResult->set('assignedUserId', $user->get('id'));
        $exportResult->set('teamsIds', array_column($user->get('teams')->toArray(), 'id'));

        if (!empty($data['exportByChannelId'])) {
            $exportResult->set('channelId', $data['exportByChannelId']);
        }

        $this->getEntityManager()->saveEntity($exportResult);

        $data['exportResultId'] = $exportResult->get('id');

        // prepare name
        $name = sprintf($this->translate('exportName'), '<span style="font-style:italic">' . $data['feed']['name'] . '</span>');

        return $this
            ->getInjection('queueManager')
            ->push($name, 'QueueManagerExport', $data);
    }

    /**
     * @param string $channelId
     *
     * @return Entity|null
     */
    protected function getChannel(string $channelId): ?Entity
    {
        return $this->getEntityManager()->getEntity('Channel', $channelId);
    }

    /**
     * @param Entity $channel
     *
     * @return array
     */
    protected function getChannelFeeds(Entity $channel): ?EntityCollection
    {
        if (!empty($feeds = $channel->get('exportFeeds'))) {
            foreach ($feeds as $feed) {
                if (!empty($feed->get('isActive')) && empty($feed->get('deleted'))) {
                    $result[] = $feed;
                }
            }
        }

        return (empty($result)) ? null : new EntityCollection($result);
    }
}
