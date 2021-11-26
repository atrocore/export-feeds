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

namespace Export\FieldConverters;

class LinkMultipleType extends AbstractType
{
    public function convert(array &$result, array $record, array $configuration): void
    {
        echo '<pre>';
        print_r('123');
        die();
//        $field = $configuration['field'] . 'Id';
//        $column = $configuration['column'];
//
//        $result[$column] = null;
//        if (isset($record[$field]) && $record[$field] !== null) {
//            $result[$column] = (string)$record[$field];
//        }

//        $params = [];
//        if (!empty($configuration['channelId'])) {
//            $params['exportByChannelId'] = $configuration['channelId'];
//        }
//
//        try {
//            $foreignResult = $this->findLinkedEntities($entity, $record['id'], $field, $params);
//        } catch (\Throwable $e) {
//            $GLOBALS['log']->error('Export. Can not get foreign entities: ' . $e->getMessage());
//        }
//
//        if (empty($configuration['exportIntoSeparateColumns'])) {
//            $result[$column] = $nullValue;
//        }
//
//        if (!empty($foreignResult['total'])) {
//            $foreignEntity = $this->getMetadata()->get(['entityDefs', $entity, 'links', $field, 'entity']);
//
//            if (isset($foreignResult['collection'])) {
//                $foreignList = $foreignResult['collection']->toArray();
//            } else {
//                $foreignList = $foreignResult['list'];
//            }
//
//            $exportBy = isset($configuration['exportBy']) ? $configuration['exportBy'] : ['id'];
//
//            $links = [];
//            foreach ($foreignList as $foreignData) {
//                $fieldResult = [];
//                foreach ($exportBy as $v) {
//                    $foreignType = (string)$this->getMetadata()->get(['entityDefs', $foreignEntity, 'fields', $v, 'type'], 'varchar');
//                    $fieldResult[] = $this->prepareSimpleType($foreignType, $foreignData, $v, $configuration);
//                }
//                $links[] = implode($fieldDelimiterForRelation, self::escapeValues($fieldResult, $fieldDelimiterForRelation));
//            }
//
//            if (!empty($configuration['exportIntoSeparateColumns'])) {
//                foreach ($links as $k => $link) {
//                    $columnName = $column . '_' . ($k + 1);
//                    $result[$columnName] = self::escapeValue($link, $delimiter);
//                }
//            } else {
//                $result[$column] = implode($delimiter, self::escapeValues($links, $delimiter));
//            }
//        }
    }
}
