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

namespace Export\DataConvertor;

class ProductAttributeValueConvertor extends Convertor
{
    /**
     * @inheritDoc
     */
    public function convert(array $record, array $configuration): array
    {
        if ($configuration['field'] === 'value' && !empty($record['attributeId'])) {
            // prepare valueModifiers
            $valueModifiers = [];
            if (!empty($configuration['valueModifier'])) {
                foreach ($configuration['valueModifier'] as $item) {
                    if (preg_match_all("/^{$record['attributeCode']}\:(.*)$/", $item, $matches)) {
                        $valueModifiers[] = $matches[1][0];
                    }
                }
            }
            $configuration['valueModifier'] = $valueModifiers;

            $attribute = $this->getAttributeById($record['attributeId']);
            $type = $attribute->get('type');
            if ($type === 'rangeFloat' || $type === "rangeInt") {
                $configuration['field'] = 'valueFrom';
            }

            return $this->convertType($this->getTypeForAttribute($record['attributeId'], 'value'), $record, $configuration);
        }

        return parent::convert($record, $configuration);
    }
}
