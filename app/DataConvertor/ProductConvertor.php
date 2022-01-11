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
            $language = !$v['isAttributeMultiLang'] ? 'main' : $configuration['locale'];
            $checkScope = empty($configuration['channelId']) ? $v['scope'] == 'Global' : $v['scope'] == 'Channel' && $configuration['channelId'] == $v['channelId'];

            if ($v['language'] === $language && $v['attributeId'] == $configuration['attributeId'] && $checkScope) {
                $result = $this->convertType($v['attributeType'], $v, array_merge($configuration, ['field' => 'value']), $toString);
                break;
            }
        }

        return $result;
    }
}
