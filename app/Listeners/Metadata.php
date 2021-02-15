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

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class ExportFeedEntity
 */
class Metadata extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function modify(Event $event): void
    {
        $this->addTypes($event);
    }

    /**
     * @param Event $event
     */
    protected function addTypes(Event $event): void
    {
        $data = $event->getArgument('data');
        $allowedTypes = $data['entityDefs']['ExportFeed']['fields']['type']['options'];

        if (!empty($data['app']['export']['type'])) {
            $types = $data['app']['export']['type'];
            foreach ($types as $type => $class) {
                if (in_array($type, $allowedTypes, true) || !$this->isAllowedType($type, $event)) {
                    continue;
                }
                if (!empty($data['entityDefs']['ExportFeed']['fields']['type']['options'])) {
                    $allowedTypes[] = $type;
                }
            }
        }

        $data['entityDefs']['ExportFeed']['fields']['type']['options'] = $allowedTypes;
        $event->setArgument('data', $data);
    }

    /**
     * @param string $type
     * @param Event $event
     * @return bool
     */
    protected function isAllowedType(string $type, Event $event): bool
    {
        $result = true;
        $methodCheck = 'isAllowedType' . ucfirst($type);
        if (method_exists($this, $methodCheck)) {
            $result = $this->{$methodCheck}($event);
        }

        return $result;
    }
    /**
     * @param Event $event
     * @return bool
     */
    protected function isAllowedTypeProductImage(Event $event): bool
    {
        return $this->getMetadata()->isModuleInstalled('Dam');
    }

    /**
     * @param Event $event
     * @return bool
     */
    protected function isAllowedTypeProductAsset(Event $event): bool
    {
        return $this->isAllowedTypeProductImage($event);
    }
}
