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

namespace Export\DataConvertor;

use Pim\Services\ProductAttributeValue;

/**
 * Class Product
 */
class Product extends Base
{
    /**
     * @var array
     */
    private $productAttributes = [];

    /**
     * @var array
     */
    private $columnData = [];

    /**
     * @inheritDoc
     */
    public function convert(array $record, array $configuration): array
    {
        if (isset($configuration['attributeId'])) {
            return $this->convertAttributeValue($record, $configuration);
        }

        return parent::convert($record, $configuration);
    }

    /**
     * @inheritDoc
     */
    public function prepareResult(array $result, array $configuration): array
    {
//        $configuration['exportByChannelId']

        $preparedResult = [];
        foreach ($result as $k => $rows) {
            foreach ($rows as $column => $value) {
                if (isset($this->columnData[$column])) {
                    $column = $this->columnData[$column];
                }
                $preparedResult[$k][$column] = $value;
            }
        }

        return $preparedResult;
    }

    /**
     * @param array $record
     * @param array $configuration
     *
     * @return array
     */
    protected function convertAttributeValue(array $record, array $configuration): array
    {
        $result[$configuration['column']] = null;

        /**
         * Find needed product attribute
         */
        foreach ($this->getProductAttributes($record['id']) as $v) {
            if ($v['attributeId'] == $configuration['attributeId'] && $v['scope'] == 'Global' && empty($productAttribute['isLocale'])) {
                $productAttribute = $v;
                break 1;
            }
        }
        if (!empty($configuration['channelId'])) {
            foreach ($this->getProductAttributes($record['id']) as $v) {
                if (
                    $v['attributeId'] == $configuration['attributeId']
                    && $v['scope'] == 'Channel'
                    && $configuration['channelId'] == $v['channelId']
                    && empty($productAttribute['isLocale'])
                ) {
                    $productAttribute = $v;
                    break 1;
                }
            }
        }

        if (!empty($productAttribute)) {
            $result[$configuration['column']] = $this->prepareSimpleType($productAttribute['attributeType'], $productAttribute, 'value', $configuration['delimiter']);
        }

        return $result;
    }

    /**
     * @param array $record
     * @param array $configuration
     *
     * @return array
     */
    protected function convertProductAttributeValues(array $record, array $configuration): array
    {
        $result = [];

        if (!empty($productAttributes = $this->getProductAttributes($record['id']))) {
            $exportBy = isset($configuration['exportBy']) ? $configuration['exportBy'] : ['id'];
            foreach ($productAttributes as $productAttribute) {
                $fieldResult = [];
                foreach ($exportBy as $v) {
                    $fieldResult[] = $this->prepareSimpleType($productAttribute['attributeType'], $productAttribute, $v, $configuration['delimiter']);
                }


                $locale = '';
                if (!empty($productAttribute['isLocale'])) {
                    $parts = explode(ProductAttributeValue::LOCALE_IN_ID_SEPARATOR, $productAttribute['id']);
                    $locale = $parts[1];
                }

                $columnName = implode('_', [$productAttribute['attributeId'], $locale, $productAttribute['channelId']]);

                $attributeLabel = $configuration['attributeColumn'] === 'attributeName' ? $productAttribute['attributeName'] : $productAttribute['attributeCode'];
                $channelLabel = 'Global';
                if ($productAttribute['scope'] === 'Channel') {
                    $channelLabel = $configuration['attributeColumn'] === 'attributeName' ? $productAttribute['channelName'] : $productAttribute['channelCode'];
                }

                $this->columnData[$columnName] = $attributeLabel . ' | ' . $channelLabel;

                $result[$columnName] = implode('|', $fieldResult);
            }
        }

        return $result;
    }

    /**
     * @param string $productId
     *
     * @return array
     */
    protected function getProductAttributes(string $productId): array
    {
        if (!isset($this->productAttributes[$productId])) {
            $this->productAttributes[$productId] = [];

            try {
                $foreignResult = $this->getService('Product')->findLinkedEntities($productId, 'productAttributeValues', []);
            } catch (\Throwable $e) {
                $GLOBALS['log']->error('Export. Can not get product attributes: ' . $e->getMessage());
            }

            if (!empty($foreignResult)) {
                if (isset($foreignResult['collection'])) {
                    $this->productAttributes[$productId] = $foreignResult['collection']->toArray();
                } else {
                    $this->productAttributes[$productId] = $foreignResult['list'];
                }
            }
        }

        return $this->productAttributes[$productId];
    }
}
