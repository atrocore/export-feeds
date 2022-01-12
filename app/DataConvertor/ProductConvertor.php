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

class ProductConvertor extends Convertor
{
    public function convert(array $record, array $configuration, bool $toString = false): array
    {
        if (isset($configuration['attributeId'])) {
            if (empty($this->getMetadata()->get(['entityDefs', 'ProductAttributeValue', 'fields', 'boolValue']))) {
                return $this->convertAttributeValueForPim1Dot3DotX($record, $configuration, $toString);
            }
            return $this->convertAttributeValue($record, $configuration, $toString);
        }

        return parent::convert($record, $configuration, $toString);
    }

    protected function convertAttributeValue(array $record, array $configuration, bool $toString = false): array
    {
        if (empty($record['pavs'])) {
            return [];
        }

        $result[$configuration['column']] = $configuration['markForNotLinkedAttribute'];

        foreach ($record['pavs'] as $v) {
            if ($this->isLanguageEquals($v, $configuration) && $v['attributeId'] == $configuration['attributeId'] && $v['scope'] == 'Global') {
                $productAttribute = $v;
                break 1;
            }
        }

        if (!empty($configuration['channelId'])) {
            foreach ($record['pavs'] as $v) {
                if (
                    $this->isLanguageEquals($v, $configuration)
                    && $v['attributeId'] == $configuration['attributeId']
                    && $v['scope'] == 'Channel'
                    && $configuration['channelId'] == $v['channelId']
                ) {
                    $productAttribute = $v;
                    break 1;
                }
            }
        }

        if (!empty($productAttribute)) {
            // exit if replaceAttributeValues disabled
            if (empty($configuration['replaceAttributeValues']) && $productAttribute['scope'] === 'Global' && !empty($configuration['channelId'])) {
                return $result;
            }
            $result = $this->convertType($productAttribute['attributeType'], $productAttribute, array_merge($configuration, ['field' => 'value']), $toString);
        }

        return $result;
    }

    /**
     * @deprecated This method will be removed soon
     */
    protected function convertAttributeValueForPim1Dot3DotX(array $record, array $configuration, bool $toString = false): array
    {
        if (empty($record['pavs'])) {
            return [];
        }

        $result[$configuration['column']] = $configuration['markForNotLinkedAttribute'];

        foreach ($record['pavs'] as $v) {
            if ($v['attributeId'] == $configuration['attributeId'] && $v['scope'] == 'Global') {
                $productAttribute = $v;
                break 1;
            }
        }

        if (!empty($configuration['channelId'])) {
            foreach ($record['pavs'] as $v) {
                if ($v['attributeId'] == $configuration['attributeId'] && $v['scope'] == 'Channel' && $configuration['channelId'] == $v['channelId']) {
                    $productAttribute = $v;
                    break 1;
                }
            }
        }

        if (!empty($productAttribute)) {
            // exit if replaceAttributeValues disabled
            if (empty($configuration['replaceAttributeValues']) && $productAttribute['scope'] === 'Global' && !empty($configuration['channelId'])) {
                return $result;
            }

            $valueField = 'value';
            if (!empty($configuration['locale']) && $configuration['locale'] !== 'mainLocale') {
                $valueField .= ucfirst(Util::toCamelCase(strtolower($configuration['locale'])));
            }

            $result = $this->convertType($productAttribute['attributeType'], $productAttribute, array_merge($configuration, ['field' => $valueField]), $toString);
        }

        return $result;
    }

    protected function isLanguageEquals(array $pav, array $configuration): bool
    {
        $language = !$pav['isAttributeMultiLang'] ? 'main' : $configuration['locale'];

        return $pav['language'] === $language;
    }
}
