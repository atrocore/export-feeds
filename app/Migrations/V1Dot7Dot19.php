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

class V1Dot7Dot19 extends Base
{
    public function up(): void
    {
        $this->getPDO()->exec("ALTER TABLE export_configurator_item ADD virtual_fields LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT '(DC2Type:jsonObject)'");
    }

    public function down(): void
    {
        $this->getPDO()->exec("ALTER TABLE export_configurator_item DROP virtual_fields");
    }
}
