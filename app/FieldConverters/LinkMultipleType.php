<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

declare(strict_types=1);

namespace Export\FieldConverters;

class LinkMultipleType extends LinkType
{
    public function convertToString(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $column = $configuration['column'];
        $entity = $configuration['entity'];

        $foreignEntity = $this->getForeignEntityName($entity, $field);

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

        $params['disableCount'] = true;

        try {
            $foreignResult = $this->findLinkedEntities($entity, $record, $field, $params);
        } catch (\Throwable $e) {
            $GLOBALS['log']->error('Export. Can not get foreign entities: ' . $e->getMessage());
        }

        if (empty($configuration['exportIntoSeparateColumns'])) {
            $result[$column] = $configuration['markForNoRelation'];
        }

        $foreignList = [];
        if (isset($foreignResult['collection'])) {
            $foreignList = $foreignResult['collection']->toArray();
        } elseif (isset($foreignResult['list'])) {
            $foreignList = $foreignResult['list'];
        }

        $links = [];
        if (empty($foreignList)) {
            $links[] = $configuration['markForNoRelation'];
        }

        $foreignList = array_slice($foreignList, 0, $params['maxSize']);

        $exportBy = isset($configuration['exportBy']) ? $configuration['exportBy'] : ['id'];

        if ($configuration['zip'] && in_array($foreignEntity, ['Asset', 'ProductAsset'])) {
            $result['__assetPaths'] = [];
            foreach ($foreignList as $foreignData) {
                $attachment = $this->convertor->getEntity('Attachment', $foreignData['fileId']);
                $result['__assetPaths'][] = $attachment->getFilePath();
            }
        }

        foreach ($foreignList as $foreignData) {
            $fieldResult = [];
            foreach ($exportBy as $v) {
                $assetUrl = $this->prepareAssetUrl($v, $foreignEntity, $foreignData);
                if ($assetUrl !== null) {
                    $fieldResult[$v] = $assetUrl;
                    if ($configuration['zip']) {
                        $result['__assetPaths'][] = str_replace(rtrim($this->convertor->getConfig()->get('siteUrl'), '/') . '/', '', $assetUrl);
                    }
                    continue 1;
                }

                $foreignType = $this->convertor->getMetadata()->get(['entityDefs', $foreignEntity, 'fields', $v, 'type'], 'varchar');

                $this->prepareExportByField($foreignList, $foreignEntity, $v, $foreignType, $foreignData);

                // prepare type for product attribute value
                if ($entity === 'Product' && $field === 'productAttributeValues' && $v === 'value') {
                    $foreignType = $foreignData['attributeType'] === 'asset' ? 'varchar' : $foreignData['attributeType'];
                }

                $foreignConfiguration = array_merge($configuration, ['field' => $v]);
                $this->convertForeignType($fieldResult, (string)$foreignType, $foreignConfiguration, $foreignData, $v, $record);
            }

            $links[] = implode($configuration['fieldDelimiterForRelation'], $fieldResult);
        }

        if (!empty($configuration['exportIntoSeparateColumns'])) {
            $k = 0;
            foreach ($links as $k => $link) {
                $columnName = $column;
                if (isset($foreignList[$k])) {
                    foreach ($foreignList[$k] as $relField => $relVal) {
                        if (is_array($relVal) || is_object($relVal)) {
                            continue 1;
                        }
                        $columnName = str_replace('{{' . $relField . '}}', (string)$relVal, $columnName);
                    }
                }

                if ($columnName === $column) {
                    $columnName = $column . '_' . ($k + 1);
                }

                $result[$columnName] = $link;
            }
            if (!empty($configuration['limitRelation']) && is_int($configuration['limitRelation'])) {
                while ($k < ($configuration['limitRelation'] - 1)) {
                    $k++;
                    $columnName = $column . '_' . ($k + 1);
                    $result[$columnName] = $configuration['markForNoRelation'];
                }
            }
        } else {
            $preparedLinks = [];
            foreach ($links as $link) {
                $preparedLinks[] = is_array($link) ? json_encode($link) : (string)$link;
            }
            $result[$column] = implode($configuration['delimiter'], $preparedLinks);
        }
    }

    protected function findLinkedEntities(string $entity, array $record, string $field, array $params): array
    {
        $records = $this->getMemoryStorage()->get('exportRecordsPart') ?? [];

        return $this->convertor->findLinkedEntities($records, $entity, $record['id'], $field, $params);
    }
}
