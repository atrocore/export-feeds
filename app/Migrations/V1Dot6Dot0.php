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

namespace Export\Migrations;

use Treo\Core\Migration\Base;

class V1Dot6Dot0 extends Base
{
    public function up(): void
    {
        $fromSchema = $this->getSchema()->getCurrentSchema();
        $toSchema = clone $fromSchema;

        try {
            $toSchema->getTable('export_feed')->dropColumn('jobs_max');
            $toSchema->getTable('export_feed')->addColumn('template', 'text', $this->getDbFieldParams([]));
        } catch (\Throwable $e) {
        }

        $this->migrateSchema($fromSchema, $toSchema);
    }

    public function down(): void
    {
        $fromSchema = $this->getSchema()->getCurrentSchema();
        $toSchema = clone $fromSchema;

        try {
            $toSchema->getTable('export_feed')->addColumn('jobs_max', 'int', $this->getDbFieldParams(['default' => 10]));
            $toSchema->getTable('export_feed')->dropColumn('template');
        } catch (\Throwable $e) {
        }

        $this->migrateSchema($fromSchema, $toSchema);
    }
}
