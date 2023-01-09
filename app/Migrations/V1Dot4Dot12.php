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

use Treo\Core\Migration\Base;

class V1Dot4Dot12 extends Base
{
    public function up(): void
    {
        $this->execute("ALTER TABLE `export_configurator_item` ADD filter_field VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->execute("ALTER TABLE `export_configurator_item` ADD filter_field_value MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci");
    }

    public function down(): void
    {
        $this->execute("ALTER TABLE `export_configurator_item` DROP filter_field");
        $this->execute("ALTER TABLE `export_configurator_item` DROP filter_field_value");
    }

    protected function execute(string $sql)
    {
        try {
            $this->getPDO()->exec($sql);
        } catch (\Throwable $e) {
            // ignore all
        }
    }
}
