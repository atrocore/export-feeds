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

class V1Dot3Dot0 extends Base
{
    public function up(): void
    {
        $this->execute("CREATE TABLE `export_configurator_item` (`id` VARCHAR(24) NOT NULL COLLATE utf8mb4_unicode_ci, `name` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `type` VARCHAR(255) DEFAULT 'Field' COLLATE utf8mb4_unicode_ci, `created_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `attribute_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `export_feed_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX `IDX_ATTRIBUTE_ID` (attribute_id), INDEX `IDX_EXPORT_FEED_ID` (export_feed_id), INDEX `IDX_NAME` (name, deleted), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("ALTER TABLE `export_configurator_item` ADD `column` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->execute("ALTER TABLE `export_configurator_item` ADD column_type VARCHAR(255) DEFAULT 'name' COLLATE utf8mb4_unicode_ci");
        $this->execute("ALTER TABLE `export_configurator_item` ADD export_by MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->execute("ALTER TABLE `export_configurator_item` ADD export_into_separate_columns TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci");
        $this->execute("ALTER TABLE `export_configurator_item` ADD sort_order INT DEFAULT NULL COLLATE utf8mb4_unicode_ci");

        $records = $this->getPDO()->query("SELECT * FROM `export_feed` WHERE `type`='simple'")->fetchAll(\PDO::FETCH_ASSOC);
        if (!empty($records)) {
            $em = (new \Treo\Core\Application())->getContainer()->get('entityManager');
            foreach ($records as $record) {
                $data = !empty($record['data']) ? json_decode($record['data'], true) : [];
                try {
                    $feed = $em->getEntity('ExportFeed', $record['id']);
                    $feed->setFeedField('fileType', $record['file_type']);
                    $feed->setFeedField('isFileHeaderRow', !empty($record['is_file_header_row']));
                    $feed->setFeedField('csvFieldDelimiter', $record['csv_field_delimiter']);
                    $feed->setFeedField('csvTextQualifier', $record['csv_text_qualifier']);
                    $feed->setFeedField('entity', isset($data['entity']) ? $data['entity'] : '');
                    $feed->setFeedField('delimiter', isset($data['delimiter']) ? $data['delimiter'] : '');
                    $feed->setFeedField('emptyValue', isset($data['emptyValue']) ? $data['emptyValue'] : '');
                    $feed->setFeedField('nullValue', isset($data['nullValue']) ? $data['nullValue'] : '');
                    $feed->setFeedField('thousandSeparator', isset($data['thousandSeparator']) ? $data['thousandSeparator'] : '');
                    $feed->setFeedField('decimalMark', isset($data['decimalMark']) ? $data['decimalMark'] : '');
                    $feed->setFeedField('markForNotLinkedAttribute', isset($data['markForNotLinkedAttribute']) ? $data['markForNotLinkedAttribute'] : '');
                    $feed->setFeedField('fieldDelimiterForRelation', isset($data['fieldDelimiterForRelation']) ? $data['fieldDelimiterForRelation'] : '');
                    $feed->setFeedField('allFields', !empty($data['allFields']));
                    $em->saveEntity($feed, ['skipAll' => true]);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
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
