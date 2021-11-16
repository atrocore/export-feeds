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

use Espo\Core\Exceptions\BadRequest;
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

    public function getColumnLabel(string $colName, array $configuration, int $num): string
    {
        if (isset($this->columnData[$colName])) {
            if (!isset($this->columnData[$colName]['attributeLabel'])) {
                $attribute = $this->getEntityManager()->getEntity('Attribute', $this->columnData[$colName]['attributeId']);
                throw new BadRequest(sprintf($this->translate('noAttributeLabel', 'exceptions', 'ExportFeed'), $attribute->get('name'), $this->columnData[$colName]['locale']));
            }

            $label = $this->columnData[$colName]['attributeLabel'];

            if (empty($configuration['exportByChannelId'])) {
                $fieldDelimiterForRelation = \Export\DataConvertor\Base::DELIMITER;
                if (!empty($configuration['feed']['data']['fieldDelimiterForRelation'])) {
                    $fieldDelimiterForRelation = $configuration['feed']['data']['fieldDelimiterForRelation'];
                }

                $label .= $fieldDelimiterForRelation . self::escapeValue($this->columnData[$colName]['channelLabel'], $fieldDelimiterForRelation);
            }

            return $label;
        }

        return parent::getColumnLabel($colName, $configuration, $num);
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

        $locale = !empty($configuration['locale']) && $configuration['locale'] !== 'mainLocale' ? $configuration['locale'] : null;

        foreach ($this->getProductAttributes($record['id']) as $v) {
            if ($v['attributeId'] == $configuration['attributeId'] && $v['scope'] == 'Global' && $v['locale'] == $locale) {
                $productAttribute = $v;
                break 1;
            }
        }

        if (!empty($configuration['channelId'])) {
            foreach ($this->getProductAttributes($record['id']) as $v) {
                if ($v['attributeId'] == $configuration['attributeId'] && $v['scope'] == 'Channel' && $v['locale'] == $locale && $configuration['channelId'] == $v['channelId']) {
                    $productAttribute = $v;
                    break 1;
                }
            }
        }

        if (!empty($productAttribute)) {
            $result[$configuration['column']] = $this->prepareSimpleType($productAttribute['attributeType'], $productAttribute, 'value', $configuration);
        } else {
            $result[$configuration['column']] = $configuration['markForNotLinkedAttribute'];
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
            $fieldDelimiterForRelation = $configuration['fieldDelimiterForRelation'];
            foreach ($productAttributes as $productAttribute) {
                $fieldResult = [];
                foreach ($exportBy as $v) {
                    $fieldResult[] = $this->prepareSimpleType($productAttribute['attributeType'], $productAttribute, $v, $configuration);
                }

                $locale = '';
                if (!empty($productAttribute['isLocale'])) {
                    $parts = explode(ProductAttributeValue::LOCALE_IN_ID_SEPARATOR, $productAttribute['id']);
                    $locale = $parts[1];
                }

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

                $attrLocale = empty($locale) ? 'mainLocale' : $locale;
                if (!empty($productAttribute['attributeIsMultilang']) && !empty($configuration['channelLocales']) && !in_array($attrLocale, $configuration['channelLocales'])) {
                    continue 1;
                }

                $columnName = self::createColumnName($productAttribute['attributeId'], $locale, (string)$productAttribute['channelId']);

                $this->columnData[$columnName] = [
                    'attributeId'    => $productAttribute['attributeId'],
                    'attributeLabel' => $attributeLabel,
                    'locale'         => $locale,
                    'channelId'      => $productAttribute['channelId'],
                    'channelLabel'   => $channelLabel
                ];

                $result[$columnName] = implode($fieldDelimiterForRelation, self::escapeValues($fieldResult, $fieldDelimiterForRelation));
            }

            /**
             * Filter columns by channel
             */
            if (!empty($configuration['channelId'])) {
                // channel value to general value
                foreach ($this->columnData as $key => $val) {
                    if ($val['channelId'] === $configuration['channelId']) {
                        $channelColumn = self::createColumnName($val['attributeId'], $val['locale'], $val['channelId']);
                        if (!self::isEmpty($result[$channelColumn])) {
                            $result[self::createColumnName($val['attributeId'], $val['locale'], '')] = $result[$channelColumn];
                        }
                    }
                }

                // remove channels columns
                foreach ($this->columnData as $key => $val) {
                    if (!empty($val['channelId']) && !empty($val['channelId'])) {
                        unset($this->columnData[$key]);
                        unset($result[self::createColumnName($val['attributeId'], $val['locale'], $val['channelId'])]);
                        continue 1;
                    }
                }
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
