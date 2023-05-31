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

class V1Dot4Dot43 extends Base
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        try {
            $items = $this
                ->getPDO()
                ->query("SELECT * FROM `export_configurator_item` WHERE deleted=0 AND value_modifier IS NOT NULL")
                ->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $items = [];
        }

        foreach ($items as $item) {
            if (!empty($item['value_modifier'])) {
                $valueModifiers = [];
                foreach (explode('||', (string)$item['value_modifier']) as $valueModifier) {
                    if (!empty($valueModifier)) {
                        $valueModifiers[] = $valueModifier;
                    }
                }
                $this->execute("UPDATE `export_configurator_item` SET value_modifier='" . json_encode($valueModifiers) . "' WHERE id='{$item['id']}'");
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        throw new Error('Downgrade is prohibited!');
    }

    protected function execute(string $query): void
    {
        try {
            $this->getPDO()->exec($query);
        } catch (\Throwable $e) {
        }
    }
}
