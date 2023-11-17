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

    protected function findLinkedEntities(string $entity, array $record, string $field, array $params)
    {
        $cache = $this->convertor->getCache('extensibleEnumOptions') ?? [];

        $result = [];

        foreach ($record[$field] as $id) {
            if (!isset($cache[$id])) {
                $service = $this->convertor->getService('ExtensibleEnumOption');
                $service->isExport = true;

                $option = $this->convertor->getEntityManager()->getRepository('ExtensibleEnumOption')->get($id);

                $count = $this->convertor->getEntityManager()->getRepository('ExtensibleEnumOption')
                    ->select(['id'])
                    ->where(['extensibleEnumId' => $option->get('extensibleEnumId')])
                    ->count();

                if ($count <= $this->convertor->getConfig()->get('maxCountOfCachedListOptions', 2000)) {
                    $params['where'] = [['type' => 'equals', 'attribute' => 'extensibleEnumId', 'value' => $option->get('extensibleEnumId')]];
                    $options = $service->findEntities($params);
                } else {
                    $params['where'] = [['type' => 'in', 'attribute' => 'id', 'value' => $record[$field]]];
                    $options = $service->findEntities($params);
                }

                foreach ($options['collection'] as $option) {
                    $cache[$option->get('id')] = $option;
                }

                $this->convertor->putCache('extensibleEnumOptions', $cache);
            }

            $result[] = $cache[$id];
        }

        return ['collection' => new EntityCollection($result, 'ExtensibleEnumOption'), 'total' => -2];
    }
}
