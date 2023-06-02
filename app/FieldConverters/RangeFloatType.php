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

class RangeFloatType extends FloatType
{
    public function convert(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $fieldFrom = $field . 'From';
        $fieldTo = $field . 'To';
        $unitField = $field . 'UnitId';

        $column = $configuration['column'];

        $valueFrom = array_key_exists($fieldFrom, $record) && $record[$fieldFrom] !== null ? (float)$record[$fieldFrom] : null;
        $valueTo = array_key_exists($fieldTo, $record) && $record[$fieldTo] !== null ? (float)$record[$fieldTo] : null;
        $unitId = isset($record[$unitField]) ? $record[$unitField] : null;

        switch ($configuration['attributeValue']) {
            case 'valueFrom':
                $result[$column] = $valueFrom;
                break;
            case 'valueTo':
                $result[$column] = $valueTo;
                break;
            case 'unit':
                $result[$column] = empty($unitId) ? null : $this->getUnitName($unitId);
                break;
            default:
                $result[$column] = (array_key_exists($fieldFrom, $record) || array_key_exists($fieldTo, $record)) ? $valueFrom . ' — ' . $valueTo : null;
        }

        $this->applyValueModifiers($configuration, $result[$column]);
    }

    public function convertToString(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $fieldFrom = $field . 'From';
        $fieldTo = $field . 'To';
        $unitField = $field . 'UnitId';

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
                $valueFrom = $this->floatToNumber((float)$record[$fieldFrom], $decimalMark, $thousandSeparator);
            }
        }

        $valueTo = $nullValue;
        if (isset($record[$fieldTo])) {
            if (empty($record[$fieldTo]) && $record[$fieldTo] != 0) {
                $valueTo = $record[$fieldTo] === null ? $nullValue : $emptyValue;
            } else {
                $valueTo = $this->floatToNumber((float)$record[$fieldTo], $decimalMark, $thousandSeparator);
            }
        }

        $unitId = isset($record[$unitField]) ? $record[$unitField] : null;

        $attributeField = $configuration['attributeValue'];
        switch ($attributeField) {
            case 'valueFrom':
                $result[$column] = $valueFrom;
                break;
            case 'valueTo':
                $result[$column] = $valueTo;
                break;
            case 'unit':
                $result[$column] = empty($unitId) ? $nullValue : $this->getUnitName($unitId);
                break;
            default:
                $result[$column] = (array_key_exists($fieldFrom, $record) || array_key_exists($fieldTo, $record)) ? $valueFrom . ' — ' . $valueTo : $nullValue;
        }


        $this->applyValueModifiers($configuration, $result[$column]);
    }
}
