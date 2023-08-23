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

class ValueWithUnitType extends AbstractType
{

    private $emptyValue;
    private $nullValue;

    public function convertToString(array &$result, array $record, array $configuration): void
    {
        $column = $configuration['column'];
        $this->emptyValue = $configuration['emptyValue'];
        $this->nullValue = $configuration['nullValue'];

        $attribute = $this->convertor->getAttributeById($record['attributeId']);
        $attributeType = $attribute->get('type');

        $unitResult = $this->convertor->convertType('unit', $record, array_merge($configuration, ['field' => 'valueUnit', 'exportBy' => ['name']]))[$column];

        if (in_array($attributeType, ['rangeFloat', 'rangeInt'])) {
            $type = $attributeType === 'rangeFloat' ? 'float' : 'int';
            $valueFromResult = $this->convertor->convertType($type, $record, array_merge($configuration, ['field' => 'valueFrom']))[$column];
            $valueToResult = $this->convertor->convertType($type, $record, array_merge($configuration, ['field' => 'valueTo']))[$column];
            $result[$column] = "";

            if (!$this->isNullorEmptyResult($valueFromResult) && !$this->isNullorEmptyResult($valueToResult)) {
                $result[$column] = "$valueFromResult - $valueToResult";
            } else if (!$this->isNullorEmptyResult($valueFromResult)) {
                $result[$column] = ">= $valueFromResult";
            } else if (!$this->isNullorEmptyResult($valueToResult)) {
                $result[$column] = "<= $valueToResult";
            }

            $result[$column] .= " $unitResult";
        } else {
            $valueResult = $this->convertor->convertType($attributeType, $record, array_merge($configuration, ['field' => 'value']))[$column];
            $result[$column] = "$valueResult $unitResult";
        }
    }

    public function isNullorEmptyResult(string $result = null): bool
    {
        return in_array($result, [$this->nullValue, $this->emptyValue]);
    }
}
