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
use Pim\Services\ProductAttributeValue;

/**
 * Class Product
 */
class Product extends Record
{
    public const ATTRIBUTE_SEPARATOR = ' | ';

    /**
     * @var array
     */
    protected $productAttributes = [];

    /**
     * @var null|ProductAttributeValue
     */
    protected $pavService = null;

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

        $channelId = isset($row['channelId']) ? $row['channelId'] : '';

        if (!empty($pav = $this->getAttribute($row['attributeId'], $channelId))) {
            $result[$row['column']] = $this->preparePavValue($pav, $data['delimiter']);
        }

        return $result;
    }

    /**
     * @param Entity $entity
     * @param array  $row
     * @param array  $data
     *
     * @return array
     */
    protected function prepareProductAttributeValues(Entity $entity, array $row, array $data): array
    {
        $result = [];

        $column = $row['column'];
        $delimiter = $data['delimiter'];

        $linked = $entity->get('productAttributeValues');
        $exportBy = isset($row['exportBy']) ? $row['exportBy'] : ['id'];

        if (count($linked) > 0) {
            $delimiter = !empty($delimiter) ? $delimiter : ',';

            $links = [];
            foreach ($linked as $item) {
                $this->getPavService()->prepareEntityForOutput($item);

                $fieldResult = [];
                foreach ($exportBy as $v) {
                    if ($item->hasField($v)) {
                        if ($v === 'value') {
                            $fieldResult[] = $this->preparePavValue($item, $delimiter);
                        } else {
                            $fieldResult[] = $item->get($v);
                        }

                    }
                }
                $links[] = implode('|', $fieldResult);
            }

            if (!empty($row['exportIntoSeparateColumns'])) {
                foreach ($links as $k => $link) {
                    if (empty($attribute = $item->get('attribute'))) {
                        continue 1;
                    }
                    if ($item->get('scope') === 'Channel' && empty($channel = $item->get('channel'))) {
                        continue 1;
                    }

                    $columnName = [];
                    if ($row['attributeColumn'] === 'attributeName') {
                        $columnName[] .= $attribute->get('name');
                    }
                    $columnName[] = $attribute->get('code');
                    $columnName[] = $item->get('scope') === 'Channel' ? $channel->get('code') : 'Global';

                    $result[implode(self::ATTRIBUTE_SEPARATOR, $columnName)] = $link;
                }
            } else {
                $result[$column] = implode($delimiter, $links);
            }
        } else {
            $result[$column] = null;
        }

        return $result;
    }

    /**
     * @param Entity $pav
     * @param string $delimiter
     *
     * @return mixed
     */
    protected function preparePavValue(Entity $pav, string $delimiter)
    {
        $this->getPavService()->prepareEntityForOutput($pav);

        $result = $pav->get('value');

        switch ($pav->get('attributeType')) {
            case 'array':
            case 'arrayMultiLang':
            case 'multiEnum':
            case 'multiEnumMultiLang':
                $result = implode($delimiter, Json::decode($pav->get('value'), true));
                break;
            case 'unit':
                $result = $pav->get('value') . ' ' . $pav->get('valueUnit');
                break;
            case 'currency':
                $result = $pav->get('value') . ' ' . $pav->get('valueCurrency');
                break;
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

    protected function getPavService(): ProductAttributeValue
    {
        if (empty($this->pavService)) {
            $this->pavService = $this->container->get('serviceFactory')->create('ProductAttributeValue');
        }

        return $this->pavService;
    }
}
