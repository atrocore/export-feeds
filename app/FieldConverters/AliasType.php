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

class AliasType extends AbstractType
{
    public function convertToString(array &$result, array $record, array $configuration): void
    {
        $column = $configuration['column'];
        $attribute = $this->convertor->getAttributeById($record['attributeId']);
        $aliasedAttributes = $attribute->get('aliasedAttributes');

        if (!empty($aliasedAttributes) && count($aliasedAttributes) > 0) {
            $pavResults = [];
            foreach ($aliasedAttributes as $aliasedAttribute) {
                $aliasConfiguration = json_decode(json_encode($configuration), true);
                $aliasConfiguration['column'] = $column;
                $aliasConfiguration['exportBy'] = ['name'];
                $aliasConfiguration['attributeId'] = $aliasedAttribute->get('id');
                $aliasConfiguration['attributeName'] = $aliasedAttribute->get('name');
                $type = $aliasedAttribute->get('type');
                if ($type == 'rangeInt' || $type == 'rangeFloat') {
                    $aliasConfiguration['attributeValue'] = 'valueFrom';
                }

                $subResult = $this->convertor->convert(["id" => $record['productId']], $aliasConfiguration);
                if (isset($subResult[$column]) && $subResult[$column] != $aliasConfiguration['markForNotLinkedAttribute']) {
                    $pavResults[] = $subResult[$column];
                }
            }

            $result[$column] = implode(' | ', $pavResults);
        }
    }
}
