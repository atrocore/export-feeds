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

use Espo\ORM\EntityCollection;

class ExtensibleMultiEnumType extends LinkMultipleType
{
    protected function getFieldName(string $field): string
    {
        return $field;
    }

    protected function getForeignEntityName(string $entity, string $field): string
    {
        return 'ExtensibleEnumOption';
    }

    protected function findLinkedEntities(string $entity, array $record, string $field, array $params): array
    {
        $collection = new EntityCollection([], 'ExtensibleEnumOption');

        // load to memory
        $key = $this->loadLinkDataToMemory($record, $entity, $field);

        $linkedEntitiesKeys = $this->getMemoryStorage()->get($this->convertor->keyName) ?? [];

        if (!isset($linkedEntitiesKeys[$entity][$key])) {
            return ['collection' => $collection];
        }

        foreach ($linkedEntitiesKeys[$entity][$key] as $v) {
            $option = $this->getMemoryStorage()->get($v);
            foreach ($record[$field] as $id) {
                if ($id === $option->get('id')) {
                    $collection->append($option);
                }
            }
        }

        return ['collection' => $collection];
    }
}
