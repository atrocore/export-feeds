<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.md, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
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
