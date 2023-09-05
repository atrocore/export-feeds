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

class V1Dot3Dot1 extends Base
{
    public function up(): void
    {
        $this->execute("ALTER TABLE `export_feed` ADD jobs_max INT DEFAULT '10' COLLATE utf8mb4_unicode_ci");
        $this->execute("UPDATE `export_feed` SET jobs_max=10 WHERE deleted=0");
        $this->execute("ALTER TABLE `export_job` ADD data MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->execute("ALTER TABLE `export_feed` ADD `limit` INT DEFAULT '2000' COLLATE utf8mb4_unicode_ci");
        $this->execute("UPDATE `export_feed` SET `limit`=2000 WHERE deleted=0");
        $this->execute("ALTER TABLE `export_feed` ADD separate_job TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci");
        $this->execute("ALTER TABLE `export_job` ADD sort_order INT DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->execute("ALTER TABLE `export_job` ADD count INT DEFAULT '0' COLLATE utf8mb4_unicode_ci");

        $this->execute("ALTER TABLE `scheduled_job` ADD export_feed_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->execute("CREATE INDEX IDX_EXPORT_FEED_ID ON `scheduled_job` (export_feed_id)");

        try {
            /** @var \Espo\ORM\EntityManager $em */
            $em = (new \Atro\Core\Application())->getContainer()->get('entityManager');
            $em->getRepository('ExportJob')->removeCollection();
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
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
