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

class ArrayType extends AbstractType
{
    public function convert(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $column = $configuration['column'];

        $result[$column] = null;
        if (isset($record[$field]) && $record[$field] !== null) {
            $result[$column] = (array)$record[$field];
        }
    }

    public function convertToString(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $column = $configuration['column'];
        $emptyValue = $configuration['emptyValue'];
        $nullValue = $configuration['nullValue'];
        $delimiter = $configuration['delimiter'];

        $result[$column] = $nullValue;
        if (isset($record[$field])) {
            if (empty($record[$field])) {
                $result[$column] = $record[$field] === null ? $nullValue : $emptyValue;
            } else {
                if (is_array($record[$field])) {
                    $result[$column] = implode($delimiter, $record[$field]);
                }
            }
        }
    }
}
