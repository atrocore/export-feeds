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

namespace Export\ExportData;

use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Treo\Core\Utils\Metadata;
use Treo\Traits\ContainerTrait;

/**
 * Class Record
 */
class Record
{
    use ContainerTrait;

    /**
     * @param Entity $entity
     * @param array $data
     * @param string $delimiter
     *
     * @return array
     */
    public function prepare(Entity $entity, array $data, string $delimiter): array
    {
        $result = null;

        if (isset($data['field'])) {
            $method = 'prepare' . ucfirst($data['field']);

            if (method_exists($this, $method)) {
                $result = $this->$method($entity, $data, $delimiter);
            } else {
                $result = $this->default($entity, $data, $delimiter);
            }
        }

        return $result;
    }

    /**
     * @param Entity $entity
     * @param array $data
     * @param string $delimiter
     *
     * @return array
     */
    protected function default(Entity $entity, array $data, string $delimiter): array
    {
        $field = $data['field'];
        $column = $data['column'];

        $result = [];

        // check is field is 'id'
        if ($field == 'id') {
            return [$column => $entity->get('id')];
        }

        // get field type
        $type = $this->getMetadata()->get(['entityDefs', $entity->getEntityType(), 'fields', $field, 'type']);

        if (isset($type)) {
            switch ($type) {
                case 'array':
                case 'arrayMultiLang':
                case 'multiEnum':
                case 'multiEnumMultiLang':
                    $delimiter = !empty($delimiter) ? $delimiter : ',';
                    $result[$column] = implode($delimiter, $entity->get($field));

                    break;
                case 'bool':
                    $result[$column] = (int)$entity->get($field);

                    break;
                case 'link':
                    $linked = $entity->get($field);
                    $exportBy = isset($data['exportBy']) ? $data['exportBy'] : 'id';

                    if (!empty($linked)) {
                        if ($linked instanceof Entity && $linked->hasField($exportBy)) {
                            $result[$column] = $linked->get($exportBy);
                        }
                    } else {
                        $result[$column] = null;
                    }

                    break;
                case 'linkMultiple':
                    $linked = $entity->get($field);
                    $exportBy = isset($data['exportBy']) ? $data['exportBy'] : 'id';

                    if ($linked instanceof EntityCollection) {
                        if (count($linked) > 0) {
                            $delimiter = !empty($delimiter) ? $delimiter : ',';

                            foreach ($linked as $item) {
                                if ($item->hasField($exportBy)) {
                                    $result[$column][] = $item->get($exportBy);
                                }
                            }

                            $result[$column] = implode($delimiter, $result[$column]);
                        } else {
                            $result[$column] = null;
                        }
                    }

                    break;
                case 'currency':
                    $result[$column] = $entity->get($field);
                    $result[$column . ' Currency'] = $entity->get($field . 'Currency');

                    break;
                case 'unit':
                    $result[$column] = $entity->get($field);
                    $result[$column . ' Unit'] = $entity->get($field . 'Unit');

                    break;
                default:
                    $result[$column] = $entity->get($field);
            }
        }

        return $result;
    }

    /**
     * @return Metadata
     */
    protected function getMetadata(): Metadata
    {
        return $this->getContainer()->get('metadata');
    }
}
