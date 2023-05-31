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
        $exportTypes = [];
        foreach ($this->getMetadata()->get(['app', 'services'], []) as $serviceName => $serviceClassName) {
            if (strpos($serviceName, 'ExportType') !== false) {
                $exportTypes[] = lcfirst(str_replace('ExportType', '', $serviceName));
            }
        }

        // filtering by ExportFeed types
        $params['where'][] = [
            'type'      => 'in',
            'attribute' => 'type',
            'value'     => $exportTypes
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

    protected function boolFilterOnlyExportFailed24Hours(array &$result): void
    {
        $result['whereClause'][] = [
            'id' => $this->getExportFeedFilteredIds(1)
        ];
    }

    protected function boolFilterOnlyExportFailed7Days(array &$result): void
    {
        $result['whereClause'][] = [
            'id' => $this->getExportFeedFilteredIds(7)
        ];
    }

    protected function boolFilterOnlyExportFailed28Days(array &$result): void
    {
        $result['whereClause'][] = [
            'id' => $this->getExportFeedFilteredIds(28)
        ];
    }

    protected function getExportFeedFilteredIds(int $interval): array
    {
        $query = "SELECT exp.id
            FROM `export_feed` exp
            JOIN export_job exj ON exj.export_feed_id = exp.id
            WHERE exj.state = 'Failed'
            AND exj.start >= DATE_SUB(NOW(), INTERVAL $interval DAY)";

        return array_column(
            $this->getEntityManager()->getPDO()->query($query)->fetchAll(\PDO::FETCH_ASSOC),
            'id'
        );
    }
}
