<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore GmbH (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

declare(strict_types=1);

namespace Export\Migrations;

use Atro\Core\Migration\Base;

class V1Dot7Dot31 extends Base
{
    public function up(): void
    {
        $this->exec('ALTER TABLE export_job ADD queue_item_id VARCHAR(24) DEFAULT NULL');
        $this->exec('CREATE INDEX IDX_EXPORT_JOB_QUEUE_ITEM_ID ON export_job (queue_item_id)');
        $this->exec('CREATE INDEX IDX_EXPORT_JOB_QUEUE_ITEM_ID_DELETED ON export_job (queue_item_id, deleted)');

        try {
            $items = $this->getPDO()->query("SELECT export_configurator_item.id as id FROM export_configurator_item inner join attribute on export_configurator_item.attribute_id = attribute.id where attribute_value='value' and attribute.type = 'varchar' and export_configurator_item.deleted=0")->fetchAll(\PDO::FETCH_ASSOC);
        }catch (\Throwable $e){
            $items = [];
        }

        $ids = [];
        foreach ($items as $item) {
            $ids[] = $item['id'];
        }
        if (!empty($ids)) {
            $search = "('" . join("','", $ids) . "')";
            $this->getPDO()->exec("UPDATE export_configurator_item SET attribute_value='valueString' WHERE id in $search");
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
