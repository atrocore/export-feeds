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
        $this->getPDO()->exec("UPDATE export_feed SET `language`='main' WHERE `language`='mainLocale'");
        $this->getPDO()->exec("UPDATE export_configurator_item SET locale='main' WHERE locale='' OR locale IS NULL OR locale='mainLocale'");
        $this->getPDO()->exec("ALTER TABLE export_configurator_item CHANGE locale `language` VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`");

        $records = $this->getPDO()->query(
            "SELECT eci.*, ef.data as ef_data FROM export_configurator_item eci INNER JOIN export_feed ef ON ef.id=eci.export_feed_id WHERE eci.deleted=0 AND ef.deleted=0 AND eci.type='Field'"
        )->fetchAll(\PDO::FETCH_ASSOC);

        /** @var \Espo\Core\Utils\Metadata $metadata */
        $metadata = (new \Espo\Core\Application())->getContainer()->get('metadata');

        foreach ($records as $record) {
            $data = @json_decode($record['ef_data'], true);
            if (empty($data) || empty($data['entity'])) {
                continue;
            }

            $fieldDefs = $metadata->get(['entityDefs', $data['entity'], 'fields', $record['name']]);
            if (empty($fieldDefs['multilangField'])) {
                continue;
            }
            $this->getPDO()->exec(
                "UPDATE export_configurator_item SET `name`='{$fieldDefs['multilangField']}', `language`='{$fieldDefs['multilangLocale']}' WHERE id='{$record['id']}'"
            );
        }
    }

    public function down(): void
    {
        throw new Error('Downgrade is prohibited!');
    }
}
