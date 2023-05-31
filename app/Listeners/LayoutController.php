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

use Espo\Core\Utils\Json;
use Treo\Core\EventManager\Event;
use Treo\Listeners\AbstractListener;

class LayoutController extends AbstractListener
{
    public function afterActionRead(Event $event): void
    {
        $scope = $event->getArgument('params')['scope'];

        $name = $event->getArgument('params')['name'];

        $method = 'modify' . $scope . ucfirst($name);

        if (method_exists($this, $method)) {
            $this->{$method}($event);
        }
    }

    protected function modifyScheduledJobDetail(Event $event): void
    {
        $result = Json::decode($event->getArgument('result'), true);

        $newRows = [];
        foreach ($result[0]['rows'] as $row) {
            $newRows[] = $row;
            if ($row[0]['name'] === 'job') {
                $newRows[] = [['name' => 'exportFeed'], false];
                $newRows[] = [['name' => 'exportFeeds'], false];
                if(!$this->checkIfFieldExists('maximumHoursToLookBack', $result[0]['rows'])){
                    $newRows[] = [['name' => 'maximumHoursToLookBack'], false];
                }
            }
        }

        $result[0]['rows'] = $newRows;

        $event->setArgument('result', Json::encode($result));
    }

    public function checkIfFieldExists(string $fieldName, array $array): bool
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if ($this->checkIfFieldExists($fieldName, $value)) {
                    return true;
                }
            } else if ($key === 'name' && $value === $fieldName) {
                return true;
            }
        }
        return false;
    }
}
