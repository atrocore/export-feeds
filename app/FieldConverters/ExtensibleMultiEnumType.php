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
    protected function getForeignEntityName(string $entity, string $field): string
    {
        return 'ExtensibleEnumOption';
    }

    protected function findLinkedEntities(string $entity, array $record, string $field, array $params): array
    {
        $collection = new EntityCollection([], 'ExtensibleEnumOption');

        $key = 'extensibleEnumOptions';

        // load to memory
        $this->loadToMemory($entity, $record, $field, $params, $key);

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

    protected function loadToMemory(string $entity, array $record, string $field, array $params, string &$key): void
    {
        $records = $this->getMemoryStorage()->get('exportRecordsPart') ?? [];

        // if PAV
        if (!empty($record['attributeType'])) {
            $records = [];
            foreach ($this->getMemoryStorage()->get('pavCollection') as $pav) {
                if ($pav->get('attributeId') === $record['attributeId']) {
                    $records[] = $pav->toArray();
                }
            }
            $key .= '_' . $record['attributeId'];
        } else {
            $key .= '_' . $field;
        }

        $optionsIds = [];
        foreach ($records as $v) {
            $optionsIds = array_merge($optionsIds, $v[$field]);
        }

        $params['offset'] = 0;
        $params['maxSize'] = $this->convertor->getConfig()->get('exportMemoryItemsCount', 10000);
        $params['disableCount'] = true;
        $params['where'] = [['type' => 'in', 'attribute' => 'id', 'value' => $record[$field]]];

        $res = $this->convertor->getService('ExtensibleEnumOption')->findEntities($params);

        foreach ($res['collection'] as $re) {
            $itemKey = $this->convertor->getEntityManager()->getRepository($re->getEntityType())->getCacheKey($re->get('id'));
            $this->getMemoryStorage()->set($itemKey, $re);
            $linkedEntitiesKeys[$entity][$key][] = $itemKey;
        }
        $this->getMemoryStorage()->set($this->convertor->keyName, $linkedEntitiesKeys);
    }
}
