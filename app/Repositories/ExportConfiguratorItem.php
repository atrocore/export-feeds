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

namespace Export\Repositories;

use Doctrine\DBAL\ParameterType;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Templates\Repositories\Base;
use Espo\ORM\Entity;
use Export\Core\ValueModifier;

class ExportConfiguratorItem extends Base
{
    protected function beforeSave(Entity $entity, array $options = [])
    {
        if ($entity->isNew() && !$entity->has('previousItem')) {
            $where = ['exportFeedId' => $entity->get('exportFeedId')];
            if (!empty($entity->get('sheetId'))) {
                $where = ['sheetId' => $entity->get('sheetId')];
            }
            $last = $this->select(['sortOrder'])->where($where)->order('sortOrder', 'DESC')->findOne();
            $entity->set('sortOrder', empty($last) ? 0 : $last->get('sortOrder') + 10);
        }

        if ($entity->isAttributeChanged('attributeId')) {
            if (!empty($attribute = $entity->get('attribute'))) {
                $entity->set('name', $attribute->get('name'));
            }
        }

        if ($entity->isAttributeChanged('valueModifier') && !empty($entity->get('valueModifier'))) {
            $this->getInjection(ValueModifier::class)->apply($this->getValueModifiers($entity));
        }

        if (empty($entity->get('language'))) {
            $entity->set('language', 'main');
        }

        parent::beforeSave($entity, $options);
    }

    public function updatePosition(Entity $entity): void
    {
        $qb = $this
            ->getConnection()
            ->createQueryBuilder()
            ->select('id')
            ->from('export_configurator_item')
            ->where('deleted=:false')
            ->setParameter('false', false, ParameterType::BOOLEAN);

        if (!empty($entity->get('sheetId'))) {
            $qb->andWhere('sheet_id=:sheetId')->setParameter('sheetId', $entity->get('sheetId'));
        } else {
            $qb->andWhere('export_feed_id=:exportFeedId')->setParameter('exportFeedId', $entity->get('exportFeedId'));
        }

        $res = $qb->orderBy('sort_order', 'ASC')->fetchFirstColumn();

        $ids = [];
        if (empty($entity->get('previousItem'))) {
            $ids[] = $entity->get('id');
        }

        foreach ($res as $id) {
            if (!in_array($id, $ids)) {
                $ids[] = $id;
            }
            if ($id === $entity->get('previousItem')) {
                $ids[] = $entity->get('id');
            }
        }

        foreach ($ids as $k => $id) {
            $this
                ->getConnection()
                ->createQueryBuilder()
                ->update('export_configurator_item')
                ->set('sort_order', ':sortOrder')->setParameter('sortOrder', $k * 10)
                ->where('id=:id')->setParameter('id', $id)
                ->executeQuery();
        }
    }

    protected function afterSave(Entity $entity, array $options = [])
    {
        parent::afterSave($entity, $options);

        if ($entity->isAttributeChanged('previousItem')) {
            $this->updatePosition($entity);
        }
    }

    protected function getValueModifiers(Entity $entity)
    {
        $valueModifiers = $entity->get('valueModifier');
        if ($entity->get('name') !== 'value') {
            return $valueModifiers;
        }

        $exportFeed = $this->getEntityManager()->getRepository('ExportFeed')->get($entity->get('exportFeedId'));
        if ($exportFeed->getFeedField('entity') !== 'ProductAttributeValue') {
            return $valueModifiers;
        }

        $preparedValueModifiers = [];
        foreach ($valueModifiers as $modifier) {
            $parts = explode(':', $modifier);
            $attributeCode = array_shift($parts);

            $attribute = $this
                ->getEntityManager()
                ->getRepository('Attribute')
                ->select(['id'])
                ->where(['code' => $attributeCode])
                ->findOne();

            if (empty($attribute)) {
                throw new BadRequest(sprintf($this->getInjection('language')->translate('noSuchAttribute', 'exceptions', 'ExportConfiguratorItem'), $attributeCode));
            }
            $preparedValueModifiers[] = implode(':', $parts);
        }

        return $preparedValueModifiers;
    }

    protected function init()
    {
        parent::init();

        $this->addDependency(ValueModifier::class);
        $this->addDependency('language');
    }
}
