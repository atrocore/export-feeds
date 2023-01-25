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

namespace Export\Migrations;

use Espo\Core\Exceptions\Error;
use Treo\Core\Migration\Base;

class V1Dot6Dot20 extends Base
{
    public function up(): void
    {
        $this->getPDO()->exec("UPDATE export_configurator_item SET locale='main' WHERE locale='mainLocale'");

        // всі мовні записи потрібно мутувати в новий спосіб збереження. nameDeDe -> name + de_DE
    }

    public function down(): void
    {
        throw new Error('Downgrade is prohibited!');
    }
}
