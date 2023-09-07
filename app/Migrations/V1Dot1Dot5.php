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

/**
 * Class V1Dot1Dot5
 */
class V1Dot1Dot5 extends Base
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $this->execute("CREATE TABLE `channel_export_feed` (`id` INT AUTO_INCREMENT NOT NULL UNIQUE COLLATE utf8mb4_unicode_ci, `export_feed_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `channel_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, INDEX `IDX_1C2B8D61C168910B` (export_feed_id), INDEX `IDX_1C2B8D6172F5A1AA` (channel_id), UNIQUE INDEX `UNIQ_1C2B8D61C168910B72F5A1AA` (export_feed_id, channel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("DROP INDEX IDX_CHANNEL_ID ON `export_feed`");
        $this->execute("ALTER TABLE `export_feed` DROP channel_id");
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        $this->execute("DROP TABLE channel_export_feed");
        $this->execute("ALTER TABLE `export_feed` ADD channel_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->execute("CREATE INDEX IDX_CHANNEL_ID ON `export_feed` (channel_id)");
    }

    /**
     * @param string $sql
     */
    protected function execute(string $sql)
    {
        try {
            $this->getPDO()->exec($sql);
        } catch (\Throwable $e) {
            // ignore all
        }
    }
}
