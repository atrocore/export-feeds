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

namespace Export\Entities;

use Espo\Core\Templates\Entities\Base;

class ExportConfiguratorItem extends Base
{
    protected $entityType = "ExportConfiguratorItem";

    public function _hasFileNameTemplate(): bool
    {
        return true;
    }

    public function _getFileNameTemplate()
    {
        return $this->getVirtualField('fileNameTemplate');
    }

    public function _setFileNameTemplate(?string $fileNameTemplate): void
    {
        $this->setVirtualField('fileNameTemplate', $fileNameTemplate);
    }

    public function getVirtualField(string $name)
    {
        if ($this->get('virtualFields') !== null && property_exists($this->get('virtualFields'), $name)) {
            return $this->get('virtualFields')->$name;
        }

        return null;
    }

    public function setVirtualField(string $name, $value): void
    {
        if (empty($this->get('virtualFields'))) {
            $this->set('virtualFields', new \stdClass());
        }

        $this->get('virtualFields')->$name = $value;
    }
}
