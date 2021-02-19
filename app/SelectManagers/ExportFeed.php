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

namespace Export\SelectManagers;

use Treo\Core\SelectManagers\Base;

/**
 * Class ExportFeed
 */
class ExportFeed extends Base
{
    /**
     * @inheritdoc
     */
    public function getSelectParams(array $params, $withAcl = false, $checkWherePermission = false)
    {
        // filtering by ExportFeed types
        $params['where'][] = [
            'type'      => 'in',
            'attribute' => 'type',
            'value'     => array_keys($this->getMetadata()->get(['app', 'export', 'type']))
        ];

        if (!empty($params['exportEntity'])) {
            $params['where'][] = [
                'type'      => 'in',
                'attribute' => 'id',
                'value'     => $this->getRepository()->getIdsByExportEntity((string)$params['exportEntity'])
            ];
        }

        return parent::getSelectParams($params, $withAcl, $checkWherePermission);
    }

    protected function getRepository(): \Export\Repositories\ExportFeed
    {
        return $this->getEntityManager()->getRepository('ExportFeed');
    }
}
