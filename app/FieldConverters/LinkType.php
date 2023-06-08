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

class LinkType extends AbstractType
{
    public function convertToString(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $column = $configuration['column'];
        $entity = $configuration['entity'];

        $result[$column] = $configuration['nullValue'];

        $linkId = $record[$this->getFieldName($field)];

        if (!empty($linkId)) {
            $exportBy = isset($configuration['exportBy']) ? $configuration['exportBy'] : ['id'];

            if ($this->needToCallForeignEntity($exportBy) || $configuration['zip']) {
                $foreignEntity = $this->getForeignEntityName($entity, $field);
                if (!empty($foreignEntity)) {
                    try {
                        $foreign = $this->convertor->getEntity((string)$foreignEntity, $linkId);
                    } catch (\Throwable $e) {
                        $GLOBALS['log']->error('Export. Can not get foreign entity: ' . $e->getMessage());
                    }
                }

                if (!empty($foreign)) {
                    /**
                     * For main image
                     */
                    if ($field === 'mainImage' || in_array($entity, ['Category', 'Product']) && $field === 'image') {
                        $path = $foreign->getFilePath();
                        $foreign = $foreign->getAsset();
                        if ($configuration['zip']) {
                            $result['__assetPaths'] = [[$foreign->get('name'), $path]];
                        }
                        $this->convertor->getService('Asset')->prepareEntityForOutput($foreign);
                    }

                    $foreignData = $foreign->toArray();
                    $fieldResult = [];
                    foreach ($exportBy as $v) {
                        $assetUrl = $this->prepareAssetUrl($v, $foreignEntity, $foreignData);
                        if ($assetUrl !== null) {
                            $fieldResult[$v] = $assetUrl;
                            continue 1;
                        }

                        $foreignType = (string)$this->convertor->getMetadata()->get(['entityDefs', $foreignEntity, 'fields', $v, 'type'], 'varchar');

                        $this->prepareExportByField($foreignEntity, $v, $foreignType, $foreignData);

                        $foreignConfiguration = array_merge($configuration, ['field' => $v]);
                        $this->convertForeignType($fieldResult, $foreignType, $foreignConfiguration, $foreignData, $v, $record);
                    }

                    if (!empty($fieldResult)) {
                        $result[$column] = implode($configuration['fieldDelimiterForRelation'], $fieldResult);
                    }
                } else {
                    $result[$column] = $configuration['emptyValue'];
                }
            } else {
                $fieldResult = [];
                foreach ($exportBy as $v) {
                    $key = $field . ucfirst($v);
                    if (isset($record[$key])) {
                        $fieldResult[] = $record[$key];
                    }
                }

                if (!empty($fieldResult)) {
                    $result[$column] = implode($configuration['fieldDelimiterForRelation'], $fieldResult);
                }
            }
        }

        if (is_string($result[$column])) {
            $this->applyValueModifiers($configuration, $result[$column]);
        }
    }

    protected function prepareExportByField(string $foreignEntity, string $configuratorField, string &$foreignType, array &$foreignData): void
    {
        $exportByFieldParts = explode(".", $configuratorField);
        if (count($exportByFieldParts) !== 2) {
            return;
        }

        $foreignLinkData = $this->convertor->findLinkedEntities($foreignEntity, $foreignData['id'], $exportByFieldParts[0], []);
        if (empty($foreignLinkData['total'])) {
            $foreignData[$configuratorField] = null;
            return;
        }

        $foreignData[$configuratorField] = $foreignLinkData['collection'][0]->get($exportByFieldParts[1]);

        $foreignType = $this
            ->convertor
            ->getMetadata()
            ->get(['entityDefs', $foreignLinkData['collection'][0]->getEntityType(), 'fields', $exportByFieldParts[1], 'type'], 'varchar');
    }

    protected function convertForeignType(array &$fieldResult, string $foreignType, array $foreignConfiguration, array $foreignData, string $field, array $record)
    {
        $column = $foreignConfiguration['column'];

        if ($foreignType === 'link') {
            $fieldResult[$field] = $foreignConfiguration['nullValue'];
            $fieldId = $field . 'Id';
            if (isset($record[$fieldId])) {
                if (empty($record[$fieldId])) {
                    $fieldResult[$field] = $record[$fieldId] === null ? $foreignConfiguration['nullValue'] : $foreignConfiguration['emptyValue'];
                } else {
                    $fieldResult[$field] = $record[$fieldId];
                }
            }
        } elseif ($foreignType === 'linkMultiple') {
            $fieldResult[$field] = $foreignConfiguration['nullValue'];
        } elseif ($foreignType === 'image' || $foreignType === 'asset') {
            $fieldResult[$field] = $foreignConfiguration['nullValue'];
            $fieldId = $field . 'Id';
            if (isset($record[$fieldId])) {
                if (empty($record[$fieldId])) {
                    $fieldResult[$field] = $record[$fieldId] === null ? $foreignConfiguration['nullValue'] : $foreignConfiguration['emptyValue'];
                } else {
                    if (!empty($attachment = $this->convertor->getEntity('Attachment', $record[$fieldId]))) {
                        $fieldResult[$field] = $attachment->get('url');
                    } else {
                        $fieldResult[$field] = $foreignConfiguration['emptyValue'];
                    }
                }
            }
        } else {
            $fieldResult[$field] = $this->convertor->convertType($foreignType, $foreignData, $foreignConfiguration)[$column];
        }
    }

    /**
     * @deprecated  fix this hack soon
     */
    protected function prepareAssetUrl(string $name, string $foreignEntity, array $foreignData): ?string
    {
        if (substr($name, -3) === 'Url') {
            $foreignFieldName = substr($name, 0, -3);
            if ($this->convertor->getMetadata()->get(['entityDefs', $foreignEntity, 'fields', $foreignFieldName, 'type']) === 'asset') {
                if (!empty($foreignData["{$foreignFieldName}PathsData"]['download'])) {
                    return rtrim($this->convertor->getConfig()->get('siteUrl'), '/') . '/' . $foreignData["{$foreignFieldName}PathsData"]['download'];
                }
            }
        }

        return null;
    }

    protected function getFieldName(string $field): string
    {
        return $field . 'Id';
    }

    protected function getForeignEntityName(string $entity, string $field): string
    {
        return $this->convertor->getMetadata()->get(['entityDefs', $entity, 'links', $field, 'entity']);
    }

    protected function needToCallForeignEntity(array $exportBy): bool
    {
        foreach ($exportBy as $v) {
            if (!in_array($v, ['id', 'name'])) {
                return true;
            }
        }

        return false;
    }
}
