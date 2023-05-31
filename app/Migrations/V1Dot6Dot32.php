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

use Treo\Core\Migration\Base;

class V1Dot6Dot32 extends Base
{
    public function up(): void
    {
        $this->exec("CREATE INDEX IDX_STATE ON export_job (state, deleted)");
        $this->exec("CREATE INDEX IDX_START ON export_job (start, deleted)");
        $this->exec("CREATE INDEX IDX_END ON export_job (end, deleted)");
        $this->exec("CREATE INDEX IDX_CREATED_AT ON export_job (created_at, deleted)");
        $this->exec("CREATE INDEX IDX_MODIFIED_AT ON export_job (modified_at, deleted)");
    }

    public function down(): void
    {
    }

    protected function exec(string $query): void
    {
        try {
            $this->getPDO()->exec($query);
        } catch (\Throwable $e) {
        }
    }
}
