<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
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
