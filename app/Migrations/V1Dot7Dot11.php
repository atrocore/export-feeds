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

use Atro\Core\Migration\Base;

class V1Dot7Dot11 extends Base
{
    public function up(): void
    {
        $ids = $this->getPDO()
            ->query("SELECT eci.id FROM export_configurator_item eci JOIN export_feed e on e.id=eci.export_feed_id WHERE e.deleted=0 AND e.data LIKE '%\"entity\":\"Product\"%' AND eci.deleted=0 AND eci.name='assets' AND eci.type='Field'")
            ->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($ids as $id) {
            $this->getPDO()->exec("UPDATE export_configurator_item SET name='productAssets_asset' WHERE id='$id'");
        }
    }

    public function down(): void
    {
    }
}
