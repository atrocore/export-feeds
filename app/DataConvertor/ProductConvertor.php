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
            return $this->convertAttributeValue($record, $configuration, $toString);
        }

        return parent::convert($record, $configuration, $toString);
    }

    protected function convertAttributeValue(array $record, array $configuration, bool $toString = false): array
    {
        if (empty($record['pavs'])) {
            return [];
        }

        $result = [];

        if ($toString) {
            $result[$configuration['column']] = $configuration['markForNotLinkedAttribute'];
        }

        foreach ($record['pavs'] as $v) {
            if (
                $v['language'] === $configuration['locale']
                && $v['attributeId'] == $configuration['attributeId']
                && $v['scope'] == 'Global'
            ) {
                $productAttribute = $v;
                break 1;
            }
        }

        if (!empty($configuration['channelId'])) {
            foreach ($record['pavs'] as $v) {
                if (
                    $v['language'] === $configuration['locale']
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
            $result = $this->convertType($productAttribute['attributeType'], $productAttribute, array_merge($configuration, ['field' => 'value']), $toString);
        }

        return $result;
    }
}
