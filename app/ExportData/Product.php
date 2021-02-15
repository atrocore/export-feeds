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
     * @param array $data
     * @param string $delimiter
     *
     * @return array
     */
    public function prepare(Entity $entity, array $data, string $delimiter): array
    {
        if (isset($data['attributeId'])) {
            $this->productAttributes = $entity->get('productAttributeValues');
            return $this->prepareAttributeValue($data, $delimiter);
        } else {
            return parent::prepare($entity, $data, $delimiter);
        }
    }

    /**
     * @param Entity $entity
     * @param array $data
     * @param string $delimiter
     *
     * @return array
     */
    public function prepareProductCategories(Entity $entity, array $data, string $delimiter): array
    {
        $result = null;

        $productCategories = $entity->get('productCategories');

        if (count($productCategories) > 0) {
            $delimiter = !empty($delimiter) ? $delimiter : ',';

            foreach ($productCategories as $productCategory) {
                $category = $productCategory->get('category');

                if ($productCategory->get('scope') == $data['scope'] && $category->hasField($data['exportBy'])) {
                    switch ($data['scope']) {
                        case 'Global':
                            $result[] = $category->get($data['exportBy']);
                            break;
                        case 'Channel':
                            if (isset($data['channelId'])
                                && in_array($data['channelId'], array_column($productCategory->get('channels')->toArray(), 'id'))) {
                                $result[] = $category->get($data['exportBy']);
                            }
                            break;
                    }
                }
            }

            $result = implode($delimiter, $result);
        }

        return [$data['column'] => $result];
    }

    /**
     * @param array $data
     * @param string $delimiter
     *
     * @return array
     */
    protected function prepareAttributeValue(array $data, string $delimiter): array
    {
        $result = [$data['column'] => null];

        $attributeId = $data['attributeId'];
        $scope = $data['scope'];
        $channelId = isset($data['channelId']) ? $data['channelId'] : '';

        if (!empty($type = $this->getAttributeType($attributeId))) {
            if ($type == 'unit') {
                $result[$data['column'] . ' Unit'] = null;
            }

            $attribute = $this->getAttribute($attributeId, $scope, $channelId);

            // get attribute value if attribute is found
            if ($scope == 'Channel' && (empty($attribute) || $attribute->get('value') == '')) {
                $data['scope'] = 'Global';
                $result = $this->prepareAttributeValue($data, $delimiter);
            } elseif (!empty($attribute)) {
                switch ($type) {
                    case 'array':
                    case 'arrayMultiLang':
                    case 'multiEnum':
                    case 'multiEnumMultiLang':
                        if (!empty($value = Json::decode($attribute->get('value'), true))) {
                            $result[$data['column']] = implode($delimiter, $value);
                        } elseif ($data['scope'] == 'Channel') {
                            $data['scope'] = 'Global';
                            $result = $this->prepareAttributeValue($data, $delimiter);
                        }

                        break;
                    case 'bool':
                        $result[$data['column']] = (int)$attribute->get('value');

                        break;
                    case 'unit':
                        $result[$data['column']] = $attribute->get('value');
                        $result[$data['column'].' Unit'] = $attribute->get('data')->unit;

                        break;
                    default:
                        $result[$data['column']] = $attribute->get('value');
                }
            }
        }

        return $result;
    }

    /**
     * @param string $attributeId
     * @param string $scope
     * @param string $channelId
     *
     * @return Entity|null
     */
    protected function getAttribute(string $attributeId, string $scope, string $channelId = ''): ?Entity
    {
        $result = null;

        foreach ($this->productAttributes as $item) {
            if ($item->get('attributeId') == $attributeId && $item->get('scope') == $scope) {
                switch ($scope) {
                    case 'Global':
                        $result = $item;
                        break;
                    case 'Channel':
                        if ($channelId == $item->get('channelId')) {
                            $result = $item;
                        }
                        break;
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

        $attribute = $this->getContainer()->get('entityManager')->getEntity('Attribute', $attributeId);

        if (!empty($attribute)) {
            $result = $attribute->get('type');
        }

        return $result;
    }
}
