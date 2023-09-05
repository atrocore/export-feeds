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

class V1Dot4Dot33 extends Base
{
    public function up(): void
    {
        $this->execute("ALTER TABLE `export_configurator_item` ADD offset_relation INT DEFAULT '0' COLLATE utf8mb4_unicode_ci");
        $this->execute("ALTER TABLE `export_configurator_item` ADD limit_relation INT DEFAULT '20' COLLATE utf8mb4_unicode_ci");
    }

    public function down(): void
    {
        $this->execute("ALTER TABLE `export_configurator_item` DROP offset_relation");
        $this->execute("ALTER TABLE `export_configurator_item` DROP limit_relation");
    }

    protected function execute(string $query): void
    {
        try {
            $this->getPDO()->exec($query);
        } catch (\Throwable $e) {
        }
    }
}
