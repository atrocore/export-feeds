<?php
/*
 * export Feeds
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

class V1Dot7Dot0 extends Base
{
    public function up(): void
    {
        $this->exec("ALTER TABLE export_configurator_item ADD attribute_value VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`");
        $this->exec("UPDATE export_feed SET sort_order_direction='ASC' WHERE sort_order_direction='1'");
        $this->exec("UPDATE export_feed SET sort_order_direction='DESC' WHERE sort_order_direction='2'");
    }

    public function down(): void
    {
        $this->exec("ALTER TABLE export_configurator_item DROP attribute_value");
    }

    protected function exec(string $sql)
    {
        try {
            $this->getPDO()->exec($sql);
        } catch (\Throwable $e) {
        }
    }
}
