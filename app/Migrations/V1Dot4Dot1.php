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

use Atro\Core\Migration\Base;

class V1Dot4Dot1 extends Base
{
    public function up(): void
    {
        $this->execute("DROP TABLE channel_export_feed");
        $this->execute("DROP INDEX IDX_CHANNEL_ID ON `export_job`");
        $this->execute("ALTER TABLE `export_job` DROP channel_id");

        $this->execute("ALTER TABLE `export_configurator_item` ADD scope VARCHAR(255) DEFAULT 'Global' COLLATE utf8mb4_unicode_ci");
        $this->execute("ALTER TABLE `export_configurator_item` ADD channel_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->execute("CREATE INDEX IDX_CHANNEL ON `export_configurator_item` (channel_id)");

        $this->execute("CREATE TABLE `export_feed_assigned_account` (`id` INT AUTO_INCREMENT NOT NULL UNIQUE COLLATE utf8mb4_unicode_ci, `account_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `export_feed_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, INDEX `IDX_AE6CF7839B6B5FBA` (account_id), INDEX `IDX_AE6CF783C168910B` (export_feed_id), UNIQUE INDEX `UNIQ_AE6CF7839B6B5FBAC168910B` (account_id, export_feed_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("DROP INDEX id ON `export_feed_assigned_account`");

        $this->execute("ALTER TABLE `export_feed` ADD language VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->execute("UPDATE `export_feed` SET language='mainLocale' WHERE 1");

        $this->execute("ALTER TABLE `export_feed` ADD channel_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->execute("CREATE INDEX IDX_CHANNEL_ID ON `export_feed` (channel_id)");

        $this->execute("ALTER TABLE `export_configurator_item` ADD mask VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
    }

    public function down(): void
    {
        $this->execute("CREATE TABLE `channel_export_feed` (`id` INT AUTO_INCREMENT NOT NULL UNIQUE COLLATE utf8mb4_unicode_ci, `channel_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `export_feed_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, INDEX `IDX_1C2B8D6172F5A1AA` (channel_id), INDEX `IDX_1C2B8D61C168910B` (export_feed_id), UNIQUE INDEX `UNIQ_1C2B8D6172F5A1AAC168910B` (channel_id, export_feed_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("ALTER TABLE `export_job` ADD channel_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->execute("CREATE INDEX IDX_CHANNEL_ID ON `export_job` (channel_id)");

        $this->execute("DROP INDEX IDX_CHANNEL_ID ON `export_configurator_item`");
        $this->execute("ALTER TABLE `export_configurator_item` DROP scope");
        $this->execute("ALTER TABLE `export_configurator_item` DROP channel_id");

        $this->execute("DROP TABLE export_feed_assigned_account");

        $this->execute("ALTER TABLE `export_feed` DROP language");

        $this->execute("DROP INDEX IDX_CHANNEL_ID ON `export_feed`");
        $this->execute("ALTER TABLE `export_feed` DROP channel_id");

        $this->execute("ALTER TABLE `export_feed` DROP mask");
    }

    protected function execute(string $sql)
    {
        try {
            $this->getPDO()->exec($sql);
        } catch (\Throwable $e) {
            // ignore all
        }
    }
}
