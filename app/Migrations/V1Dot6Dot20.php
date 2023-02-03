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
use Espo\Core\Utils\Util;
use Treo\Core\Migration\Base;

class V1Dot6Dot20 extends Base
{
    public function up(): void
    {
        $this->getPDO()->exec("UPDATE export_feed SET `language`='' WHERE `language`='mainLocale'");
        $this->exec("UPDATE export_configurator_item SET locale='main' WHERE locale='' OR locale IS NULL OR locale='mainLocale'");
        $this->exec("ALTER TABLE export_configurator_item CHANGE locale `language` VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`");

        $records = $this->getPDO()->query(
            "SELECT eci.*, ef.data as ef_data FROM export_configurator_item eci INNER JOIN export_feed ef ON ef.id=eci.export_feed_id WHERE eci.deleted=0 AND ef.deleted=0 AND eci.type='Field'"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $languages = [];
        if ($this->getConfig()->get('isMultilangActive')) {
            $languages = $this->getConfig()->get('inputLanguageList', []);
        }

        foreach ($languages as $language) {
            foreach ($records as $record) {
                if (empty($record['name']) || !is_string($record['name']) || mb_strlen($record['name']) < 4) {
                    continue;
                }

                $suffix = ucfirst(Util::toCamelCase(strtolower($language)));

                if ($suffix === mb_substr($record['name'], -4)) {
                    $name = $this->getPDO()->quote(mb_substr($record['name'], 0, -4));
                    $this->getPDO()->exec("UPDATE export_configurator_item SET `name`=$name, `language`='{$language}' WHERE id='{$record['id']}'");
                }
            }
        }
    }

    public function down(): void
    {
        throw new Error('Downgrade is prohibited!');
    }

    protected function exec(string $query): void
    {
        try {
            $this->getPDO()->exec($query);
        } catch (\Throwable $e) {
        }
    }
}
