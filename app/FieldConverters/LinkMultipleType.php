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

class LinkMultipleType extends LinkType
{
    public function convert(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $column = $configuration['column'];
        $entity = $configuration['entity'];

        $params = [];
        if (!empty($configuration['channelId'])) {
            $params['exportByChannelId'] = $configuration['channelId'];
        }

        try {
            $foreignResult = $this->convertor->findLinkedEntities($entity, $record['id'], $field, $params);
        } catch (\Throwable $e) {
            $GLOBALS['log']->error('Export. Can not get foreign entities: ' . $e->getMessage());
        }

        if (empty($configuration['exportIntoSeparateColumns'])) {
            $result[$column] = $this->needStringResult ? $configuration['nullValue'] : null;
        }

        if (!empty($foreignResult['total'])) {
            $foreignEntity = $this->convertor->getMetadata()->get(['entityDefs', $entity, 'links', $field, 'entity']);

            if (isset($foreignResult['collection'])) {
                $foreignList = $foreignResult['collection']->toArray();
            } else {
                $foreignList = $foreignResult['list'];
            }

            $exportBy = isset($configuration['exportBy']) ? $configuration['exportBy'] : ['id'];

            $links = [];
            foreach ($foreignList as $foreignData) {
                $fieldResult = [];
                foreach ($exportBy as $v) {
                    $foreignType = (string)$this->convertor->getMetadata()->get(['entityDefs', $foreignEntity, 'fields', $v, 'type'], 'varchar');
                    $foreignConfiguration = array_merge($configuration, ['field' => $v]);
                    $this->convertForeignType($fieldResult, $foreignType, $foreignConfiguration, $foreignData, $v, $record);
                }

                if ($this->needStringResult) {
                    $links[] = implode($configuration['fieldDelimiterForRelation'], $fieldResult);
                } else {
                    $links[] = $fieldResult;
                }
            }

            if (!empty($configuration['exportIntoSeparateColumns'])) {
                foreach ($links as $k => $link) {
                    $columnName = $column . '_' . ($k + 1);
                    $result[$columnName] = $link;
                }
            } else {
                if ($this->needStringResult) {
                    $result[$column] = implode($configuration['delimiter'], $links);
                } else {
                    $result[$column] = $links;
                }
            }
        }
        $this->needStringResult = false;
    }
}
