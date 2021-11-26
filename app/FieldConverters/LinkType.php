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
    public function convert(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $column = $configuration['column'];
        $entity = $configuration['entity'];

        $result[$column] = null;
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

                    $result[$column] = [];
                    foreach ($exportBy as $v) {
                        $foreignType = (string)$this->convertor->getMetadata()->get(['entityDefs', $foreignEntity, 'fields', $v, 'type'], 'varchar');
                        $foreignConfiguration = array_merge($configuration, ['field' => $v]);

                        if ($foreignType === 'link') {
                            $result[$column][$v] = empty($record[$v]) ? null : $record[$v];
                        } elseif ($foreignType === 'linkMultiple') {
                            $result[$column][$v] = null;
                        } else {
                            $result[$column][$v] = $this->convertor->convertType($foreignType, $foreignData, $foreignConfiguration)[$column];
                        }
                    }
                } else {
                    $result[$column] = null;
                }
            } else {
                $result[$column] = [];
                foreach ($exportBy as $v) {
                    $key = $field . ucfirst($v);
                    if (isset($record[$key])) {
                        $result[$column][$v] = $record[$key];
                    }
                }
            }
        }
    }
}
