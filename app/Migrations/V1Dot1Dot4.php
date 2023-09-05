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

/**
 * Class V1Dot1Dot4
 */
class V1Dot1Dot4 extends Base
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $this->execute("CREATE TABLE `export_result` (`id` VARCHAR(24) NOT NULL COLLATE utf8mb4_unicode_ci, `name` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `created_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `state` VARCHAR(255) DEFAULT 'Pending' COLLATE utf8mb4_unicode_ci, `start` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `end` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `assigned_user_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `channel_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `export_feed_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `file_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `owner_user_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX `IDX_CREATED_BY_ID` (created_by_id), INDEX `IDX_MODIFIED_BY_ID` (modified_by_id), INDEX `IDX_ASSIGNED_USER_ID` (assigned_user_id), INDEX `IDX_CHANNEL_ID` (channel_id), INDEX `IDX_EXPORT_FEED_ID` (export_feed_id), INDEX `IDX_OWNER_USER_ID` (owner_user_id), INDEX `IDX_NAME` (name, deleted), INDEX `IDX_ASSIGNED_USER` (assigned_user_id, deleted), INDEX `IDX_OWNER_USER` (owner_user_id, deleted), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("ALTER TABLE `export_result` ADD state_message MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci");
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        $this->execute("DROP INDEX IDX_CREATED_BY_ID ON `export_result`");
        $this->execute("DROP INDEX IDX_MODIFIED_BY_ID ON `export_result`");
        $this->execute("DROP INDEX IDX_CHANNEL_ID ON `export_result`");
        $this->execute("DROP INDEX IDX_EXPORT_FEED_ID ON `export_result`");
        $this->execute("DROP INDEX IDX_NAME ON `export_result`");
        $this->execute("DROP TABLE `export_result`");
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
