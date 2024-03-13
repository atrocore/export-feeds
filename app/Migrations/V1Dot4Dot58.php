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

class V1Dot4Dot58 extends Base
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $this->execute("ALTER TABLE export_configurator_item ADD sort_field_relation VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`");
        $this->execute("ALTER TABLE export_configurator_item ADD sort_order_relation VARCHAR(255) DEFAULT 'ASC' COLLATE `utf8mb4_unicode_ci`");
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        $this->execute("ALTER TABLE export_configurator_item DROP sort_field_relation");
        $this->execute("ALTER TABLE export_configurator_item DROP sort_order_relation");
    }

    protected function execute(string $query): void
    {
        try {
            $this->getPDO()->exec($query);
        } catch (\Throwable $e) {
        }
    }
}
