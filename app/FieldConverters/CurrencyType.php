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

namespace Export\FieldConverters;

class CurrencyType extends FloatType
{
    protected string $defaultMask = '{{value}} {{currency}}';

    public function convert(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $column = $configuration['column'];
        $mask = !empty($configuration['mask']) ? $configuration['mask'] : $this->defaultMask;

        $result[$column] = null;
        if (isset($record[$field]) && $record[$field] !== null) {
            $currency = $record[$field . 'Currency'];
            $value = (float)$record[$field];

            if ($mask === '{{value}}' || $mask === '{{Value}}') {
                $result[$column] = $value;
            } else {
                $result[$column] = str_replace(['{{value}}', '{{Value}}', '{{currency}}', '{{Currency}}'], [$value, $value, $currency, $currency], $mask);
            }
        }
    }

    public function convertToString(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $column = $configuration['column'];
        $emptyValue = $configuration['emptyValue'];
        $nullValue = $configuration['nullValue'];
        $decimalMark = $configuration['decimalMark'];
        $thousandSeparator = $configuration['thousandSeparator'];
        $mask = !empty($configuration['mask']) ? $configuration['mask'] : $this->defaultMask;

        $result[$column] = $nullValue;
        if (isset($record[$field])) {
            if (empty($record[$field]) && $record[$field] !== '0' && $record[$field] !== 0) {
                $result[$column] = $record[$field] === null ? $nullValue : $emptyValue;
            } else {
                $currency = $record[$field . 'Currency'];
                $value = $this->floatToNumber((float)$record[$field], $decimalMark, $thousandSeparator);
                $result[$column] = str_replace(['{{value}}', '{{Value}}', '{{currency}}', '{{Currency}}'], [$value, $value, $currency, $currency], $mask);
            }
        }
    }
}
