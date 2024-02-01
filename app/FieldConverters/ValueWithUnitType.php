<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
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

        if (empty($record['attributeId'])) {
            $fieldDefs = $this->getMetadata()->get(['entityDefs', $configuration['entity'], 'fields', $configuration['field']]);
            // use main field instead
            $field = $fieldDefs['mainField'];
            $type = $this->getMetadata()->get(['entityDefs', $configuration['entity'], 'fields', $field, 'type']);
            $unitResult = $record[$field . 'UnitName'] ?? '';
        } else {
            $attribute = $this->convertor->getAttributeById($record['attributeId']);
            $field = 'value';
            $type = $attribute->get('type');
            $unitResult = $this->convertor->convertType('unit', $record, array_merge($configuration, ['field' => $field . 'Unit', 'exportBy' => ['name'], 'markForNoRelation' => '']))[$column];
        }

        if (in_array($type, ['rangeFloat', 'rangeInt'])) {
            $type = $type === 'rangeFloat' ? 'float' : 'int';
            $valueFromResult = $this->convertor->convertType($type, $record, array_merge($configuration, ['field' => $field . 'From']))[$column];
            $valueToResult = $this->convertor->convertType($type, $record, array_merge($configuration, ['field' => $field . 'To']))[$column];
            $result[$column] = "";

            if (!$this->isNullorEmptyResult($valueFromResult) && !$this->isNullorEmptyResult($valueToResult)) {
                $result[$column] = "$valueFromResult - $valueToResult";
            } else if (!$this->isNullorEmptyResult($valueFromResult)) {
                $result[$column] = ">= $valueFromResult";
            } else if (!$this->isNullorEmptyResult($valueToResult)) {
                $result[$column] = "<= $valueToResult";
            }
        } else {
            $valueResult = $this->convertor->convertType($type, $record, array_merge($configuration, ['field' => $field]))[$column];
            $result[$column] = "$valueResult";
        }

        if (!empty($unitResult)) {
            $result[$column] .= " $unitResult";
        }
    }

    public function isNullorEmptyResult(string $result = null): bool
    {
        return in_array($result, [$this->nullValue, $this->emptyValue]);
    }
}
