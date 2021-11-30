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

        $result[$column] = null;
        if (isset($record[$field]) && $record[$field] !== null) {
            $result[$column] = (float)$record[$field];
        }
    }

    public function convertToString(array &$result, array $record, array $configuration): void
    {
        $this->convert($result, $record, $configuration);
    }
}
