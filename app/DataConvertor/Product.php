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

use Espo\Core\Utils\Util;
use Espo\ORM\EntityManager;
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
        $cropped = [];
        if (!empty($configuration['exportByChannelId'])) {
            foreach ($result as $k => $rows) {
                foreach ($rows as $column => $value) {
                    if (isset($this->columnData[$column])) {
                        $data = $this->columnData[$column];
                        if (!empty($data['channelId'])) {
                            continue 1;
                        }
                        $channelColumn = self::createColumnName($data['attributeId'], $data['locale'], $configuration['exportByChannelId']);
                        if (!self::isEmpty($result[$k][$channelColumn])) {
                            $cropped[$k][$column] = $result[$k][$channelColumn];
                        } else {
                            $cropped[$k][$column] = $value;
                        }
                    } else {
                        $cropped[$k][$column] = $value;
                    }
                }
            }
        } else {
            $cropped = $result;
        }

        $preparedResult = [];
        foreach ($cropped as $k => $rows) {
            foreach ($rows as $column => $value) {
                if (isset($this->columnData[$column])) {
                    if (!empty($configuration['exportByChannelId'])) {
                        $preparedResult[$k][$this->columnData[$column]['attributeLabel']] = $value;
                    } else {
                        $preparedResult[$k][$this->columnData[$column]['attributeLabel'] . ' | ' . $this->columnData[$column]['channelLabel']] = $value;
                    }
                } else {
                    $preparedResult[$k][$column] = $value;
                }
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
            if ($v['attributeId'] == $configuration['attributeId'] && $v['scope'] == 'Global') {
                $productAttribute = $v;
                break 1;
            }
        }
        if (!empty($configuration['channelId'])) {
            foreach ($this->getProductAttributes($record['id']) as $v) {
                if ($v['attributeId'] == $configuration['attributeId'] && $v['scope'] == 'Channel' && $configuration['channelId'] == $v['channelId']) {
                    $productAttribute = $v;
                    break 1;
                }
            }
        }

        if (!empty($productAttribute)) {
            $value = 'value';

            if (!empty($configuration['locale']) && $configuration['locale'] !== 'mainLocale') {
                $value = Util::toCamelCase(strtolower($value . '_' . $configuration['locale']));
            }

            $result[$configuration['column']] = $this->prepareSimpleType($productAttribute['attributeType'], $productAttribute, $value, $configuration['delimiter']);
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

                $columnName = self::createColumnName($productAttribute['attributeId'], $locale, (string)$productAttribute['channelId']);

                if (empty($configuration['attributeColumn']) || $configuration['attributeColumn'] == 'attributeName') {
                    $attributeLabel = $productAttribute['attributeName'];
                    if (!empty($productAttribute['attributeIsMultilang']) && !empty($locale)) {
                        $attribute = $this->getEntityManager()->getEntity('Attribute', $productAttribute['attributeId']);
                        $attributeLabel = $attribute->get(Util::toCamelCase(strtolower('name_' . $locale)));
                    }
                }

                if ($configuration['attributeColumn'] == 'internalAttributeName') {
                    $attributeLabel = $productAttribute['attributeName'];
                }

                if ($configuration['attributeColumn'] == 'attributeCode') {
                    $attributeLabel = $productAttribute['attributeCode'];
                }

                $channelLabel = 'Global';
                if ($productAttribute['scope'] === 'Channel') {
                    $channelLabel = $configuration['attributeColumn'] === 'attributeName' ? $productAttribute['channelName'] : $productAttribute['channelCode'];
                }

                $this->columnData[$columnName] = [
                    'attributeId'    => $productAttribute['attributeId'],
                    'attributeLabel' => $attributeLabel,
                    'locale'         => $locale,
                    'channelId'      => $productAttribute['channelId'],
                    'channelLabel'   => $channelLabel
                ];

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

    /**
     * @param string $attributeId
     * @param string $locale
     * @param string $channelId
     *
     * @return string
     */
    private static function createColumnName(string $attributeId, string $locale, string $channelId): string
    {
        return implode('_', ['attr', $attributeId, $locale, $channelId]);
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    private static function isEmpty($value): bool
    {
        return empty($value) && $value !== 0 && $value !== '0';
    }

    private function getEntityManager(): EntityManager
    {
        return $this->container->get('entityManager');
    }
}
