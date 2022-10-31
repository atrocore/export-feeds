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

namespace Export\TwigFunction;

use Pim\Entities\Category;

class PreviousCategoryId extends AbstractTwigFunction
{
    public function __construct()
    {
        $this->addDependency('entityManager');
        $this->addDependency('serviceFactory');
    }

    public function run($category)
    {
        if (empty($category) || !($category instanceof Category)) {
            return null;
        }

        $where = !empty($this->getFeedData()['data']['where']) ? $this->getFeedData()['data']['where'] : [];

        if (empty($category->get('categoryParentId'))) {
            $where[] = [
                'type'      => 'isNull',
                'attribute' => 'categoryParentId'
            ];
        } else {
            $where[] = [
                'type'      => 'equals',
                'attribute' => 'categoryParentId',
                'value'     => $category->get('categoryParentId')
            ];
        }

        $categories = $this
            ->getInjection('serviceFactory')
            ->create('Category')
            ->findEntities(['where' => $where, 'sortBy' => 'sortOrder', 'asc' => true]);

        $before = null;
        foreach ($categories['collection'] as $cat) {
            if ($cat->get('id') === $category->get('id')) {
                return $before;
            }
            $before = $cat->get('id');
        }

        return null;
    }
}
