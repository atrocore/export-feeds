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
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

declare(strict_types=1);

namespace Export\FieldConverters;

class LinkType extends AbstractType
{
    protected bool $needStringResult = false;

    public function convert(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $column = $configuration['column'];
        $entity = $configuration['entity'];

        $result[$column] = $this->needStringResult ? $configuration['nullValue'] : null;

        $linkId = $record[$field . 'Id'];

        if (!empty($linkId)) {
            $exportBy = isset($configuration['exportBy']) ? $configuration['exportBy'] : ['id'];

            $needToCallForeignEntity = false;
            foreach ($exportBy as $v) {
                if (!in_array($v, ['id', 'name'])) {
                    $needToCallForeignEntity = true;
                    break 1;
                }
            }

            if ($needToCallForeignEntity) {
                $foreignEntity = $this->convertor->getMetadata()->get(['entityDefs', $entity, 'links', $field, 'entity']);
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
                        $foreign = $foreign->getAsset();
                        $this->convertor->getService('Asset')->prepareEntityForOutput($foreign);
                    }

                    $foreignData = $foreign->toArray();
                    $fieldResult = [];
                    foreach ($exportBy as $v) {
                        $foreignType = (string)$this->convertor->getMetadata()->get(['entityDefs', $foreignEntity, 'fields', $v, 'type'], 'varchar');
                        $foreignConfiguration = array_merge($configuration, ['field' => $v]);
                        $this->convertForeignType($fieldResult, $foreignType, $foreignConfiguration, $foreignData, $v, $record);
                    }

                    if (!empty($fieldResult)) {
                        if ($this->needStringResult || !empty($configuration['convertRelationsToString'])) {
                            $result[$column] = implode($configuration['fieldDelimiterForRelation'], $fieldResult);
                        } else {
                            $result[$column] = $fieldResult;
                        }
                    }
                } else {
                    if ($this->needStringResult) {
                        $result[$column] = $configuration['emptyValue'];
                    } else {
                        $result[$column] = null;
                    }
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
                    if ($this->needStringResult || !empty($configuration['convertRelationsToString'])) {
                        $result[$column] = implode($configuration['fieldDelimiterForRelation'], $fieldResult);
                    } else {
                        $result[$column] = $fieldResult;
                    }
                }
            }
        }
        $this->needStringResult = false;

        if (is_string($result[$column])) {
            $this->applyValueModifiers($configuration, $result[$column]);
        }
    }

    public function convertToString(array &$result, array $record, array $configuration): void
    {
        $this->needStringResult = true;
        $this->convert($result, $record, $configuration);
    }

    protected function convertForeignType(array &$fieldResult, string $foreignType, array $foreignConfiguration, array $foreignData, string $field, array $record)
    {
        $column = $foreignConfiguration['column'];

        if ($foreignType === 'link') {
            $fieldResult[$field] = $this->needStringResult ? $foreignConfiguration['nullValue'] : null;
            $fieldId = $field . 'Id';
            if (isset($record[$fieldId])) {
                if (empty($record[$fieldId])) {
                    if ($this->needStringResult) {
                        $fieldResult[$field] = $record[$fieldId] === null ? $foreignConfiguration['nullValue'] : $foreignConfiguration['emptyValue'];
                    } else {
                        $fieldResult[$field] = null;
                    }
                } else {
                    $fieldResult[$field] = $record[$fieldId];
                }
            }
        } elseif ($foreignType === 'linkMultiple') {
            $fieldResult[$field] = $this->needStringResult ? $foreignConfiguration['nullValue'] : null;
        } elseif ($foreignType === 'image' || $foreignType === 'asset') {
            $fieldResult[$field] = $this->needStringResult ? $foreignConfiguration['nullValue'] : null;
            $fieldId = $field . 'Id';
            if (isset($record[$fieldId])) {
                if (empty($record[$fieldId])) {
                    if ($this->needStringResult) {
                        $fieldResult[$field] = $record[$fieldId] === null ? $foreignConfiguration['nullValue'] : $foreignConfiguration['emptyValue'];
                    } else {
                        $fieldResult[$field] = null;
                    }
                } else {
                    if (!empty($attachment = $this->convertor->getEntity('Attachment', $record[$fieldId]))) {
                        $fieldResult[$field] = $attachment->get('url');
                    } else {
                        if ($this->needStringResult) {
                            $fieldResult[$field] = $foreignConfiguration['emptyValue'];
                        } else {
                            $fieldResult[$field] = null;
                        }
                    }
                }
            }
        } else {
            $fieldResult[$field] = $this->convertor->convertType($foreignType, $foreignData, $foreignConfiguration)[$column];
        }
    }
}
