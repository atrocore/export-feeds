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

class V1Dot7Dot11 extends Base
{
    public function up(): void
    {
        $ids = $this->getPDO()
            ->query("SELECT eci.id FROM export_configurator_item eci JOIN export_feed e on e.id=eci.export_feed_id WHERE e.deleted=0 AND e.data LIKE '%\"entity\":\"Product\"%' AND eci.deleted=0 AND eci.name='assets' AND eci.type='Field'")
            ->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($ids as $id) {
            $this->getPDO()->exec("UPDATE export_configurator_item SET name='productAssets_asset' WHERE id='$id'");
        }
    }

    public function down(): void
    {
    }
}
