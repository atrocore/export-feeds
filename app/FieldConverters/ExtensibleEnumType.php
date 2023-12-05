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

class ExtensibleEnumType extends LinkType
{
    protected function getFieldName(string $field): string
    {
        return $field;
    }

    protected function getForeignEntityName(string $entity, string $field): string
    {
        return 'ExtensibleEnumOption';
    }

    protected function needToCallForeignEntity(array $exportBy): bool
    {
        return true;
    }

    public function getEntity(string $scope, string $id)
    {
        echo '<pre>';
        print_r('getEntity');
        die();

        $cache = $this->convertor->getCache('extensibleEnumOptions') ?? [];

        if (!isset($cache[$id])) {
            $service = $this->convertor->getService('ExtensibleEnumOption');

            $option = $this->convertor->getEntityManager()->getRepository('ExtensibleEnumOption')->get($id);

            $count = $this->convertor->getEntityManager()->getRepository('ExtensibleEnumOption')
                ->select(['id'])
                ->where(['extensibleEnumId' => $option->get('extensibleEnumId')])
                ->count();

            if ($count <= $this->convertor->getConfig()->get('maxCountOfCachedListOptions', 2000)) {
                $params['where'] = [['type' => 'equals', 'attribute' => 'extensibleEnumId', 'value' => $option->get('extensibleEnumId')]];
                $options = $service->findEntities($params);
                foreach ($options['collection'] as $option) {
                    $cache[$option->get('id')] = $option;
                }
            } else {
                $cache[$id] = $service->getEntity($id);
            }

            $this->convertor->putCache('extensibleEnumOptions', $cache);
        }

        return $cache[$id];
    }
}
