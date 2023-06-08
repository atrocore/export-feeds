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
 */

declare(strict_types=1);

namespace Export\AclPortal;

use Espo\Core\AclPortal\Base;
use Espo\Entities\User;
use Espo\ORM\Entity;

class ExportConfiguratorItem extends Base
{
    public function checkEntity(User $user, Entity $entity, $data, $action)
    {
        if (!empty($entity->get('exportFeedId'))) {
            $exportFeed = $this->getEntityManager()->getEntity('ExportFeed', $entity->get('exportFeedId'));
        }

        if (!empty($entity->get('sheetId'))) {
            $sheet = $this->getEntityManager()->getEntity('Sheet', $entity->get('sheetId'));
            if (!empty($sheet)) {
                $exportFeed = $sheet->get('exportFeed');
            }
        }

        if (empty($exportFeed)) {
            return false;
        }

        if (in_array($action, ['create', 'delete'])) {
            $action = 'edit';
        }

        return $this->getAclManager()->checkEntity($user, $exportFeed, $action);
    }
}

