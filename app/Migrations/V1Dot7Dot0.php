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

class V1Dot7Dot0 extends Base
{
    public function up(): void
    {
        $this->exec("ALTER TABLE export_configurator_item ADD attribute_value VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`");
        $this->exec("UPDATE export_feed SET sort_order_direction='ASC' WHERE sort_order_direction='1'");
        $this->exec("UPDATE export_feed SET sort_order_direction='DESC' WHERE sort_order_direction='2'");
    }

    public function down(): void
    {
        $this->exec("ALTER TABLE export_configurator_item DROP attribute_value");
    }

    protected function exec(string $sql)
    {
        try {
            $this->getPDO()->exec($sql);
        } catch (\Throwable $e) {
        }
    }
}
