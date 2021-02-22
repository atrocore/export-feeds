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
