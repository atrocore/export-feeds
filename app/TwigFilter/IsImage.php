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

namespace Export\TwigFilter;

use Espo\ORM\Entity;

class IsImage extends AbstractTwigFilter
{
    public function __construct()
    {
        $this->addDependency('metadata');
    }

    public function filter($value)
    {
        if (empty($value) || !is_object($value) || !($value instanceof Entity) || $value->getEntityType() !== 'Asset') {
            return false;
        }

        $fileNameParts = explode('.', $value->get("file")->get('name'));
        $fileExt = strtolower(array_pop($fileNameParts));

        return in_array($fileExt, $this->getInjection('metadata')->get('dam.image.extensions', []));
    }
}
