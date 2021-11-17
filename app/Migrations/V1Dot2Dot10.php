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

class V1Dot2Dot10 extends Base
{
    public function up(): void
    {
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
}
