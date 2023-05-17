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

class RangeIntType extends AbstractType
{
    public function convert(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $fieldFrom = $field . 'From';
        $fieldTo = $field . 'To';

        $column = $configuration['column'];

        $valueFrom = array_key_exists($fieldFrom, $record) && $record[$fieldFrom] !== null ? (int)$record[$fieldFrom] : null;
        $valueTo = array_key_exists($fieldTo, $record) && $record[$fieldTo] !== null ? (int)$record[$fieldTo] : null;

        $result[$column] = (array_key_exists($fieldFrom, $record) || array_key_exists($fieldTo, $record)) ? $valueFrom . ' — ' . $valueTo : null;

        $this->applyValueModifiers($configuration, $result[$column]);
    }

    public function convertToString(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $fieldFrom = $field . 'From';
        $fieldTo = $field . 'To';

        $column = $configuration['column'];

        $emptyValue = $configuration['emptyValue'];
        $nullValue = $configuration['nullValue'];
        $decimalMark = $configuration['decimalMark'];
        $thousandSeparator = $configuration['thousandSeparator'];

        $valueFrom = $nullValue;
        if (isset($record[$fieldFrom])) {
            if (empty($record[$fieldFrom]) && $record[$fieldFrom] != 0) {
                $valueFrom = $record[$fieldFrom] === null ? $nullValue : $emptyValue;
            } else {
                $valueFrom = number_format((float)$record[$fieldFrom], 0, $decimalMark, $thousandSeparator);
            }
        }

        $valueTo = $nullValue;
        if (isset($record[$fieldTo])) {
            if (empty($record[$fieldTo]) && $record[$fieldTo] != 0) {
                $valueTo = $record[$fieldTo] === null ? $nullValue : $emptyValue;
            } else {
                $valueTo = number_format((float)$record[$fieldTo], 0, $decimalMark, $thousandSeparator);
            }
        }

        $result[$column] = (array_key_exists($fieldFrom, $record) || array_key_exists($fieldTo, $record)) ? $valueFrom . ' — ' . $valueTo : $nullValue;

        $this->applyValueModifiers($configuration, $result[$column]);
    }
}
