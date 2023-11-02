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

use Atro\Core\Migration\Base;
use Atro\ORM\DB\RDB\Mapper;

class V1Dot7Dot31 extends Base
{
    public function up(): void
    {
//        $this->exec('ALTER TABLE export_job ADD queue_item_id VARCHAR(24) DEFAULT NULL');
//        $this->exec('CREATE INDEX IDX_EXPORT_JOB_QUEUE_ITEM_ID ON export_job (queue_item_id)');
//        $this->exec('CREATE INDEX IDX_EXPORT_JOB_QUEUE_ITEM_ID_DELETED ON export_job (queue_item_id, deleted)');

        try {
            $items = $this->getSchema()->getConnection()->createQueryBuilder()
                ->select('id')
                ->from('attribute')
                ->where('type = :type')
                ->andWhere('deleted = :false')
                ->setParameter('type', 'varchar')
                ->setParameter('false', false, Mapper::getParameterType(false))
                ->fetchAllAssociative();

        } catch (\Throwable $e) {
            $items = [];
        }

        $ids = [];
        foreach ($items as $item) {
            $ids[] = $item['id'];
        }
        if (!empty($ids)) {
            $search = "('" . join("','", $ids) . "')";

            $this->getSchema()->getConnection()->createQueryBuilder()
                ->update('export_configurator_item')
                ->set('attribute_value', ':value')
                ->where('attribute_value = :oldValue')
                ->andWhere('attribute_id in :ids')
                ->andWhere('deleted = :false')
                ->setParameter('value', 'valueString')
                ->setParameter('oldValue', 'value')
                ->setParameter('ids', $search)
                ->setParameter('false', false, Mapper::getParameterType(false))
                ->executeQuery();
        }
    }

    public function down(): void
    {
        $this->exec('DROP INDEX IDX_EXPORT_JOB_QUEUE_ITEM_ID_DELETED ON export_job');
        $this->exec('DROP INDEX IDX_EXPORT_JOB_QUEUE_ITEM_ID ON export_job');
        $this->exec('ALTER TABLE export_job DROP queue_item_id');

        $this->getPDO()->exec("UPDATE export_configurator_item SET attribute_value='value' WHERE attribute_value='valueString'");
    }

    protected function exec(string $sql): void
    {
        try {
            $this->getPDO()->exec($sql);
        } catch (\Throwable $e) {
        }
    }
}
