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

class V1Dot6Dot40 extends Base
{
    public function up(): void
    {
        $this->execute("DROP TABLE scheduled_job_export_feed");
        $this->execute("DROP INDEX IDX_208D5E79A71ECAB0 ON scheduled_job_export_feed");
        $this->execute("DROP INDEX IDX_208D5E7942B515EF ON scheduled_job_export_feed");
        $this->execute("DROP INDEX UNIQ_208D5E79A71ECAB042B515EF ON scheduled_job_export_feed");

        $this->getPDO()->exec("CREATE TABLE scheduled_job_export_feed (id INT AUTO_INCREMENT NOT NULL UNIQUE COLLATE `utf8mb4_unicode_ci`, scheduled_job_id VARCHAR(24) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, export_feed_id VARCHAR(24) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, deleted TINYINT(1) DEFAULT '0' COLLATE `utf8mb4_unicode_ci`, INDEX IDX_EB108612A71ECAB0 (scheduled_job_id), INDEX IDX_EB108612C168910B (export_feed_id), UNIQUE INDEX UNIQ_EB108612A71ECAB0C168910B (scheduled_job_id, export_feed_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB");

        $countMaximumHours = $this->getPDO()->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'scheduled_job' AND column_name = 'maximum_hours_to_look_back'")->fetchColumn();
        if ($countMaximumHours == 0) {
            $this->getPDO()->exec("ALTER TABLE scheduled_job ADD maximum_hours_to_look_back DOUBLE PRECISION DEFAULT NULL COLLATE `utf8mb4_unicode_ci`");
        }

        $this->execute("DROP INDEX id ON scheduled_job_export_feed");
    }

    public function down(): void
    {
        $this->execute("DROP INDEX IDX_208D5E79A71ECAB0 ON scheduled_job_export_feed");
        $this->execute("DROP INDEX IDX_208D5E7942B515EF ON scheduled_job_export_feed");
        $this->execute("DROP INDEX UNIQ_208D5E79A71ECAB042B515EF ON scheduled_job_export_feed");

        $this->execute("DROP TABLE scheduled_job_export_feed");
    }

    protected function execute(string $sql)
    {
        try {
            $this->getPDO()->exec($sql);
        } catch (\Throwable $e) {
        }
    }
}
