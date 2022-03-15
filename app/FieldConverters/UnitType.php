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
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

declare(strict_types=1);

namespace Export\FieldConverters;

class UnitType extends FloatType
{
    public function convert(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $column = $configuration['column'];

        $result[$column] = null;
        if (isset($record[$field]) && $record[$field] !== null) {
            $unit = $this->isPav($record) ? $record['data']['unit'] : $record[$field . 'Unit'];
            $value = (float)$record[$field];

            if ($configuration['mask'] === '{{value}}' || $configuration['mask'] === '{{Value}}') {
                $result[$column] = $value;
            } else {
                $result[$column] = str_replace(['{{value}}', '{{Value}}', '{{unit}}', '{{Unit}}'], [$value, $value, $unit, $unit], $configuration['mask']);
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

        $result[$column] = $nullValue;
        if (isset($record[$field])) {
            if (empty($record[$field]) && $record[$field] !== '0' && $record[$field] !== 0) {
                $result[$column] = $record[$field] === null ? $nullValue : $emptyValue;
            } else {
                $unit = $this->isPav($record) ? $record['data']['unit'] : $record[$field . 'Unit'];
                $value = $this->floatToNumber((float)$record[$field], $decimalMark, $thousandSeparator);
                $result[$column] = str_replace(['{{value}}', '{{Value}}', '{{unit}}', '{{Unit}}'], [$value, $value, $unit, $unit], $configuration['mask']);
            }
        }
    }
}
