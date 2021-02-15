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

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Json;
use Espo\ORM\Entity;
use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class ExportFeedEntity
 */
class ExportFeedEntity extends AbstractListener
{
    /**
     * @param Event $event
     *
     * @throws Error
     */
    public function beforeSave(Event $event)
    {
        if (!$this->isValid($event->getArgument('entity'))) {
            throw new BadRequest($this->translate(
                'configuratorSettingsIncorrect',
                'exceptions',
                'ExportFeed'
            ));
        }
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isValid(Entity $entity): bool
    {
        $result = true;
        $configuration = Json::decode(Json::encode($entity->get('data')->configuration), true);

        foreach ($configuration as $key => $item) {
            if (isset($item['attributeId'])) {
                foreach ($configuration as $k => $i) {
                    if (isset($i['attributeId']) && $key != $k && $i['attributeId'] == $item['attributeId']
                        && $i['scope'] == $item['scope']) {
                        if ($i['scope'] == 'Global' || ($i['scope'] == 'Channel' && $i['channelId'] == $item['channelId'])) {
                            $result = false;
                        }
                    }
                }
            } elseif ($entity->get('data')->entity == 'Product' && $item['field'] == 'productCategories') {
                foreach ($configuration as $k => $i) {
                    if ($i['field'] == $item['field'] && $key != $k && $i['scope'] == $item['scope']) {
                        if ($i['scope'] == 'Global' || ($i['scope'] == 'Channel' && $i['channelId'] == $item['channelId'])) {
                            $result = false;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Translate
     *
     * @param string $key
     *
     * @param string $label
     * @param string $scope
     *
     * @return string
     */
    protected function translate(string $key, string $label, $scope = ''): string
    {
        return $this->getContainer()->get('language')->translate($key, $label, $scope);
    }
}
