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

namespace Export\Loaders;

use Treo\Core\Loaders\Base;
use Export\ExportType\ExportTypeFactory;

/**
 * ExportTypeFactory loader
 */
class ExportTypeFactoryLoader extends Base
{

    /**
     * Load ExportTypeFactory
     *
     * @return ExportTypeFactory
     */
    public function load()
    {
        return (new ExportTypeFactory())->setContainer($this->getContainer());
    }
}
