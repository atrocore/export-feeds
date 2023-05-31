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

class FloatType extends AbstractType
{
    public function convert(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $column = $configuration['column'];
        $unitField = $field . 'UnitId';

        $value = null;
        $unitId = isset($record[$unitField]) ? $record[$unitField] : null;

        if (isset($record[$field]) && $record[$field] !== null) {
            $value = (float)$record[$field];
            $this->applyValueModifiers($configuration, $value);
        }

        $result[$column] = $configuration['attributeValue'] == 'unit' ?
            (empty($unitId) ? null : $this->getUnitName($unitId)) :
            $value;
    }

    public function convertToString(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $column = $configuration['column'];
        $emptyValue = $configuration['emptyValue'];
        $nullValue = $configuration['nullValue'];
        $decimalMark = $configuration['decimalMark'];
        $thousandSeparator = $configuration['thousandSeparator'];
        $unitField = $field . 'UnitId';


        $value = $nullValue;
        $unitId = isset($record[$unitField]) ? $record[$unitField] : null;
        if (isset($record[$field])) {
            if (empty($record[$field]) && $record[$field] != 0) {
                $value = $record[$field] === null ? $nullValue : $emptyValue;
            } else {
                $value = $this->floatToNumber((float)$record[$field], $decimalMark, $thousandSeparator);
                $this->applyValueModifiers($configuration, $value);
            }
        }

        $result[$column] = $configuration['attributeValue'] == 'unit' ?
            (empty($unitId) ? $nullValue : $this->getUnitName($unitId)) :
            $value;
    }

    protected function floatToNumber(float $value, $decimalMark, $thousandSeparator): string
    {
        return rtrim(rtrim(number_format($value, 10, $decimalMark, $thousandSeparator), '0'), $decimalMark);
    }
}
