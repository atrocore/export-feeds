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
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;

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
     */
    public function exportFile(\stdClass $requestData): bool
    {
        $data = [
            'id'   => Util::generateId(),
            'feed' => $this->getEntityManager()->getEntity('ExportFeed', $requestData->id)->toArray()
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
        }

        return $this->pushExport($data);
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
     * Init
     */
    protected function init()
    {
        parent::init();

        $this->addDependency('queueManager');
        $this->addDependency('language');
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
        // prepare name
        $name = sprintf($this->translate('exportName'), $data['feed']['name']);

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

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function pushChannelExport(array $data): bool
    {
        // prepare name
        $name = sprintf($this->translate('channelDataArchive'), $data['channel']['name']);

        return $this
            ->getInjection('queueManager')
            ->push($name, 'QueueManagerChannelExport', $data);
    }
}
