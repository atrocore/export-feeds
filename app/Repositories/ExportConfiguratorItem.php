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
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

declare(strict_types=1);

namespace Export\Repositories;

use Espo\Core\Templates\Repositories\Base;
use Espo\ORM\Entity;
use Export\Core\ValueModifier;

class ExportConfiguratorItem extends Base
{
    protected function beforeSave(Entity $entity, array $options = [])
    {
        if ($entity->isNew()) {
            $last = $this->select(['sortOrder'])->where(['exportFeedId' => $entity->get('exportFeedId')])->order('sortOrder', 'DESC')->findOne();
            $entity->set('sortOrder', empty($last) ? 0 : $last->get('sortOrder') + 10);
        }

        if ($entity->isAttributeChanged('attributeId')) {
            if (!empty($attribute = $entity->get('attribute'))) {
                $entity->set('name', $attribute->get('name'));
            }
        }

        if ($entity->isAttributeChanged('valueModifier') && !empty($entity->get('valueModifier'))) {
            $this->getInjection(ValueModifier::class)->apply((string)$entity->get('valueModifier'));
        }

        parent::beforeSave($entity, $options);
    }

    protected function init()
    {
        parent::init();

        $this->addDependency(ValueModifier::class);
    }
}
