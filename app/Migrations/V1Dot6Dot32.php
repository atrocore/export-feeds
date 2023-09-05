<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.md, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

declare(strict_types=1);

namespace Export\Migrations;

use Atro\Core\Migration\Base;

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
