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

class V1Dot7Dot21 extends Base
{
    public function up(): void
    {
        $this->getPDO()->exec("UPDATE export_configurator_item SET attribute_value='valueUnit' WHERE attribute_value='valueUnitId'");

        $feeds = $this->getPDO()->query("SELECT * FROM export_feed WHERE deleted=0")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($feeds as $feed) {
            $data = @json_decode($feed['data'], true);
            if (is_array($data) && isset($data['feedFields']['markForNotLinkedAttribute'])) {
                $data['feedFields']['markForNoRelation'] = $data['feedFields']['markForNotLinkedAttribute'];
                unset($data['feedFields']['markForNotLinkedAttribute']);
                $newData = $this->getPDO()->quote(json_encode($data));
                $this->getPDO()->exec("UPDATE export_feed SET data=$newData WHERE id='{$feed['id']}'");
            }
        }
    }

    public function down(): void
    {
        $this->getPDO()->exec("UPDATE export_configurator_item SET attribute_value='valueUnitId' WHERE attribute_value='valueUnit'");

        $feeds = $this->getPDO()->query("SELECT * FROM export_feed WHERE deleted=0")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($feeds as $feed) {
            $data = @json_decode($feed['data'], true);
            if (is_array($data) && isset($data['feedFields']['markForNoRelation'])) {
                $data['feedFields']['markForNotLinkedAttribute'] = $data['feedFields']['markForNoRelation'];
                unset($data['feedFields']['markForNoRelation']);
                $newData = $this->getPDO()->quote(json_encode($data));
                $this->getPDO()->exec("UPDATE export_feed SET data=$newData WHERE id='{$feed['id']}'");
            }
        }
    }
}
