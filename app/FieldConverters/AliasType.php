<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore GmbH (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
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
                if (isset($subResult[$column]) && $subResult[$column] != $aliasConfiguration['markForNoRelation']) {
                    $pavResults[] = $subResult[$column];
                }
            }

            $result[$column] = implode(' | ', $pavResults);
        }
    }
}
