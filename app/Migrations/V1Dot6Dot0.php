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
use Atro\Core\Migration\Base;

class V1Dot6Dot0 extends Base
{
    public function up(): void
    {
        $fromSchema = $this->getSchema()->getCurrentSchema();
        $toSchema = clone $fromSchema;

        try {
            $toSchema->getTable('export_feed')->dropColumn('jobs_max');
            $toSchema->getTable('export_feed')->addColumn('template', 'text', $this->getDbFieldParams([]));
            $toSchema->getTable('export_feed')->addColumn('file_type', 'varchar', $this->getDbFieldParams([]));
        } catch (\Throwable $e) {
        }

        $this->migrateSchema($fromSchema, $toSchema);

        $records = $this
            ->getSchema()
            ->getConnection()
            ->createQueryBuilder()
            ->select('*')
            ->from('export_feed')
            ->where('deleted=0')
            ->fetchAllAssociative();

        foreach ($records as $record) {
            $data = [];
            if (!empty($record['data'])) {
                $array = @json_decode((string)$record['data'], true);
                if (!empty($array)) {
                    $data = $array;
                }
            }

            if (!empty($data['feedFields']['fileType'])) {
                $this
                    ->getSchema()
                    ->getConnection()
                    ->createQueryBuilder()
                    ->update('export_feed')
                    ->set('file_type', ':fileType')->setParameter('fileType', $data['feedFields']['fileType'])
                    ->where('id=:id')->setParameter('id', $record['id'])
                    ->executeQuery();
            }
        }
    }

    public function down(): void
    {
        throw new Error('Downgrade is prohibited!');
    }
}
