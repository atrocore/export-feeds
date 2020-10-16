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
     * @param Entity|string $feed
     * @param string        $id
     *
     * @return bool
     * @throws Exceptions\Error
     */
    public function exportFile($feed, string $id = null): bool
    {
        // prepare feed
        if (!$feed instanceof Entity) {
            $feed = $this->getEntityManager()->getEntity('ExportFeed', $feed);
        }

        // prepare id
        if (empty($id)) {
            $id = Util::generateId();
        }

        // prepare data
        $data = [
            'id'   => $id,
            'feed' => $feed->toArray()
        ];

        return $this->pushExport($data);
    }

    /**
     * Export all channel feeds
     *
     * @param string $channelId
     * @param string $id
     *
     * @return bool
     */
    public function exportChannel(string $channelId, string $id = null): bool
    {
        // prepare result
        $result = false;

        if (!empty($channel = $this->getChannel($channelId)) && !empty($feeds = $this->getChannelFeeds($channel))) {
            // prepare id
            if (empty($id)) {
                $id = Util::generateId();
            }

            // prepare data
            $data = [
                'id'      => $id,
                'channel' => [
                    'id'   => $channel->get('id'),
                    'name' => $channel->get('name'),
                ],
                'catalog' => [
                    'id'   => $channel->get('catalogId'),
                    'name' => $channel->get('catalogName'),
                ],
                'feeds'   => $feeds->toArray()
            ];

            $result = $this->pushChannelExport($data);
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
