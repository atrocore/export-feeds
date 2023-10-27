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

class V1Dot7Dot31 extends Base
{
    public function up(): void
    {
        $this->exec('ALTER TABLE export_job ADD queue_item_id VARCHAR(24) DEFAULT NULL');
        $this->exec('CREATE INDEX IDX_EXPORT_JOB_QUEUE_ITEM_ID ON export_job (queue_item_id)');
        $this->exec('CREATE INDEX IDX_EXPORT_JOB_QUEUE_ITEM_ID_DELETED ON export_job (queue_item_id, deleted)');
    }

    public function down(): void
    {
        $this->exec('DROP INDEX IDX_EXPORT_JOB_QUEUE_ITEM_ID_DELETED ON export_job');
        $this->exec('DROP INDEX IDX_EXPORT_JOB_QUEUE_ITEM_ID ON export_job');
        $this->exec('ALTER TABLE export_job DROP queue_item_id');
    }

    protected function exec(string $sql): void
    {
        try {
            $this->getPDO()->exec($sql);
        } catch (\Throwable $e) {
        }
    }
}
