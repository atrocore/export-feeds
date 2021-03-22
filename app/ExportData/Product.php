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

use Espo\Core\Utils\Json;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;

/**
 * Class Product
 */
class Product extends Record
{
    /**
     * @var array
     */
    protected $productAttributes = [];

    /**
     * @param Entity $entity
     * @param array  $row
     * @param array  $data
     *
     * @return array
     */
    public function prepare(Entity $entity, array $row, array $data): array
    {
        if (isset($row['attributeId'])) {
            $this->productAttributes = $entity->get('productAttributeValues');
            return $this->prepareAttributeValue($row, $data);
        } else {
            return parent::prepare($entity, $row, $data);
        }
    }

    /**
     * @param array $row
     * @param array $data
     *
     * @return array
     */
    protected function prepareAttributeValue(array $row, array $data): array
    {
        $result = [$row['column'] => null];

        $attributeId = $row['attributeId'];
        $delimiter = $data['delimiter'];
        $channelId = isset($row['channelId']) ? $row['channelId'] : '';

        if (!empty($type = $this->getAttributeType($attributeId))) {
            $attribute = $this->getAttribute($attributeId, $channelId);

            if (!empty($attribute)) {
                switch ($type) {
                    case 'array':
                    case 'arrayMultiLang':
                    case 'multiEnum':
                    case 'multiEnumMultiLang':
                        $result[$row['column']] = implode($delimiter, Json::decode($attribute->get('value'), true));
                        break;
                    case 'bool':
                        $result[$row['column']] = (int)$attribute->get('value');
                        break;
                    case 'unit':
                        $result[$row['column']] = $attribute->get('value');
                        $result[$row['column'] . ' Unit'] = $attribute->get('data')->unit;
                        break;
                    default:
                        $result[$row['column']] = $attribute->get('value');
                }
            }
        }

        return $result;
    }

    /**
     * @param array $row
     * @param array $data
     *
     * @return array
     */
    protected function prepareAttributeValues(array $row, array $data): array
    {
        echo '<pre>';
        print_r('123');
        die();
        $result = [];

        $column = $row['column'];
        $delimiter = $data['delimiter'];

        $linked = $this->productAttributes;
        $exportBy = isset($row['exportBy']) ? $row['exportBy'] : ['id'];

        if ($linked instanceof EntityCollection) {
            if (count($linked) > 0) {
                $delimiter = !empty($delimiter) ? $delimiter : ',';

                $links = [];
                foreach ($linked as $item) {
                    if ($item instanceof Entity) {
                        $fieldResult = [];
                        foreach ($exportBy as $v) {
                            if ($item->hasField($v)) {
                                $fieldResult[] = $item->get($v);
                            }
                        }
                        $links[] = implode('|', $fieldResult);
                    }
                }

                if (!empty($row['exportIntoSeparateColumns'])) {
                    foreach ($links as $k => $link) {
                        if (!empty($row['useAttributeCodeAsColumnName'])) {
                            $attributeName = $item->get('attribute')->get('name');
                            $channelName = $item->get('scope') === 'Global' ? 'Global' : $item->get('channel')->get('name');

                            $columnName = $attributeName . ' | ' . $channelName;
                        } else {
                            $columnName = $column . ' ' . ($k + 1);
                        }

                        $result[$columnName] = $link;
                    }
                } else {
                    $result[$column] = implode($delimiter, $links);
                }
            } else {
                $result[$column] = null;
            }
        }

        return $result;
    }

    /**
     * @param string $attributeId
     * @param string $channelId
     *
     * @return Entity|null
     */
    protected function getAttribute(string $attributeId, string $channelId): ?Entity
    {
        $result = null;

        foreach ($this->productAttributes as $item) {
            if ($item->get('attributeId') == $attributeId && $item->get('scope') == 'Global') {
                $result = $item;
            }
        }

        if (!empty($channelId)) {
            foreach ($this->productAttributes as $item) {
                if ($item->get('attributeId') == $attributeId && $item->get('scope') == 'Channel' && $channelId == $item->get('channelId')) {
                    $result = $item;
                }
            }
        }

        return $result;
    }

    /**
     * @param string $attributeId
     *
     * @return string|null
     */
    protected function getAttributeType(string $attributeId): ?string
    {
        $result = null;

        $attribute = $this->getEntityManager()->getEntity('Attribute', $attributeId);

        if (!empty($attribute)) {
            $result = $attribute->get('type');
        }

        return $result;
    }
}
