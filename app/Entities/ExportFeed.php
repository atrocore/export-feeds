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

namespace Export\Entities;

use Espo\Core\Templates\Entities\Base;
use Espo\Core\Utils\Json;

class ExportFeed extends Base
{
    public const DATA_FIELD = 'feedFields';

    protected $entityType = "ExportFeed";

    public function setFeedField(string $name, $value): void
    {
        $data = [];
        if (!empty($this->get('data'))) {
            $data = Json::decode(Json::encode($this->get('data')), true);
        }

        $data[self::DATA_FIELD][$name] = $value;

        $this->set('data', $data);
    }

    public function getFeedField(string $name)
    {
        $data = $this->getFeedFields();

        if (!isset($data[$name])) {
            return null;
        }

        return $data[$name];
    }

    public function getFeedFields(): array
    {
        if (!empty($data = $this->get('data'))) {
            $data = Json::decode(Json::encode($data), true);
            if (!empty($data[self::DATA_FIELD]) && is_array($data[self::DATA_FIELD])) {
                return $data[self::DATA_FIELD];
            }
        }

        return [];
    }
}
