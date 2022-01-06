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

use Treo\Core\EventManager\Event;
use Treo\Listeners\AbstractListener;

class Metadata extends AbstractListener
{
    public function modify(Event $event): void
    {
        $data = $event->getArgument('data');

        if (!empty($data['scopes']['Channel']['entity'])) {
            $data['entityDefs']['ExportFeed']['fields']['channel']['type'] = 'link';
            $data['entityDefs']['ExportFeed']['links']['channel']['type'] = 'belongsTo';
            $data['entityDefs']['ExportFeed']['links']['channel']['entity'] = 'Channel';
        }

        $event->setArgument('data', $data);
    }
}
