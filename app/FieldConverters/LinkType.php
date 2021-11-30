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
                    $foreignData = $foreign->toArray();

                    $fieldResult = [];
                    foreach ($exportBy as $v) {
                        $foreignType = (string)$this->convertor->getMetadata()->get(['entityDefs', $foreignEntity, 'fields', $v, 'type'], 'varchar');
                        $foreignConfiguration = array_merge($configuration, ['field' => $v]);

                        if ($foreignType === 'link' && !empty($record[$v])) {
                            $fieldResult[$v] = $record[$v];
                        } elseif ($foreignType === 'linkMultiple') {
                            // ignore
                        } else {
                            $fieldResult[$v] = $this->convertor->convertType($foreignType, $foreignData, $foreignConfiguration)[$column];
                        }
                    }

                    if (!empty($fieldResult)) {
                        if ($this->needStringResult) {
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
                    if ($this->needStringResult) {
                        $result[$column] = implode($configuration['fieldDelimiterForRelation'], $fieldResult);
                    } else {
                        $result[$column] = $fieldResult;
                    }
                }
            }
        }
        $this->needStringResult = false;
    }

    public function convertToString(array &$result, array $record, array $configuration): void
    {
        $this->needStringResult = true;
        $this->convert($result, $record, $configuration);
    }
}
