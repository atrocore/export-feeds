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
        $foreignEntity = $this->convertor->getMetadata()->get(['entityDefs', $entity, 'links', $field, 'entity']);

        $sortBy = $this->convertor->getMetadata()->get(['clientDefs', $entity, 'relationshipPanels', $field, 'sortBy']);

        $params = [];
        if (!empty($sortBy)) {
            $asc = $this->convertor->getMetadata()->get(['clientDefs', $entity, 'relationshipPanels', $field, 'asc'], true);
            $params['sortBy'] = $sortBy;
            $params['asc'] = !empty($asc);
        }

        if (!empty($configuration['sortFieldRelation'])) {
            $params['sortBy'] = $configuration['sortFieldRelation'];
            $params['asc'] = $configuration['sortOrderRelation'] !== 'DESC';
        }

        $params['offset'] = empty($configuration['offsetRelation']) ? 0 : (int)$configuration['offsetRelation'];
        $params['maxSize'] = empty($configuration['limitRelation']) ? 20 : (int)$configuration['limitRelation'];

        if (!empty($configuration['channelId'])) {
            $params['exportByChannelId'] = $configuration['channelId'];
        }

        if (!empty($configuration['filterField']) && !empty($configuration['filterFieldValue'])) {
            switch ($this->convertor->getMetadata()->get(['entityDefs', $foreignEntity, 'fields', $configuration['filterField'], 'type'])) {
                case 'bool':
                    switch ($configuration['filterFieldValue']) {
                        case ['+']:
                            $params['where'] = [['type' => 'isTrue', 'attribute' => $configuration['filterField']]];
                            break;
                        case ['-']:
                            $params['where'] = [['type' => 'isFalse', 'attribute' => $configuration['filterField']]];
                            break;
                    }
                    break;
                case 'enum':
                    $params['where'] = [
                        [
                            'type'      => 'in',
                            'attribute' => $configuration['filterField'],
                            'value'     => $configuration['filterFieldValue'],
                        ]
                    ];
                    break;
                case 'multiEnum':
                    $params['where'] = [
                        [
                            'type'      => 'arrayAnyOf',
                            'attribute' => $configuration['filterField'],
                            'value'     => $configuration['filterFieldValue'],
                        ]
                    ];
                    break;
            }
        }

        if (!empty($configuration['searchFilter'])) {
            $params['where'] = !empty($configuration['searchFilter']['where']) ? $configuration['searchFilter']['where'] : [];
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
                    $assetUrl = $this->prepareAssetUrl($v, $foreignEntity, $foreignData);
                    if ($assetUrl !== null) {
                        $fieldResult[$v] = $assetUrl;
                        continue 1;
                    }

                    $foreignType = $this->convertor->getMetadata()->get(['entityDefs', $foreignEntity, 'fields', $v, 'type'], 'varchar');

                    $this->prepareExportByField($foreignEntity, $v, $foreignType, $foreignData);

                    // prepare type for product attribute value
                    if ($entity === 'Product' && $field === 'productAttributeValues' && $v === 'value') {
                        $foreignType = $foreignData['attributeType'] === 'asset' ? 'varchar' : $foreignData['attributeType'];
                    }

                    $foreignConfiguration = array_merge($configuration, ['field' => $v]);
                    $this->convertForeignType($fieldResult, (string)$foreignType, $foreignConfiguration, $foreignData, $v, $record);
                }

                if ($this->needStringResult || !empty($configuration['convertRelationsToString'])) {
                    $links[] = implode($configuration['fieldDelimiterForRelation'], $fieldResult);
                } else {
                    $links[] = $fieldResult;
                }
            }

            if (!empty($configuration['exportIntoSeparateColumns'])) {
                $k = 0;
                foreach ($links as $k => $link) {
                    $columnName = $column;
                    foreach ($foreignList[$k] as $relField => $relVal) {
                        if (is_array($relVal) || is_object($relVal)) {
                            continue 1;
                        }
                        $columnName = str_replace('{{' . $relField . '}}', (string)$relVal, $columnName);
                    }

                    if ($columnName === $column) {
                        $columnName = $column . '_' . ($k + 1);
                    }

                    $result[$columnName] = $link;
                }
                if (!empty($configuration['limitRelation']) && is_int($configuration['limitRelation'])) {
                    while ($k < $configuration['limitRelation']) {
                        $columnName = $column . '_' . ($k + 1);
                        $result[$columnName] = $this->needStringResult ? $configuration['nullValue'] : null;
                        $k++;
                    }
                }
            } else {
                if ($this->needStringResult || !empty($configuration['convertCollectionToString'])) {
                    $preparedLinks = [];
                    foreach ($links as $link) {
                        $preparedLinks[] = is_array($link) ? json_encode($link) : (string)$link;
                    }
                    $result[$column] = implode($configuration['delimiter'], $preparedLinks);
                } else {
                    $result[$column] = $links;
                }
            }
        }
        $this->needStringResult = false;
    }
}
