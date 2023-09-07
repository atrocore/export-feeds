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

class ExtensibleMultiEnumType extends LinkMultipleType
{
    protected function getForeignEntityName(string $entity, string $field): string
    {
        return 'ExtensibleEnumOption';
    }

    protected function findLinkedEntities(string $entity, array $record, string $field, array $params)
    {
        $params['where'] = [
            [
                'type'      => 'in',
                'attribute' => 'id',
                'value'     => $record[$field]
            ]
        ];

        return $this->convertor->getService('ExtensibleEnumOption')->findEntities($params);
    }
}
