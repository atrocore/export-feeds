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

namespace Export\Services;

/**
 * Class DataSyncErrorsExportDashlet
 */
class DataSyncErrorsExportDashlet extends AbstractDashletService
{
    /**
     * Int Class
     */
    public function init()
    {
        parent::init();

        $this->addDependency('metadata');
    }

    /**
     * Get Export failed feeds
     *
     * @return array
     * @throws \Espo\Core\Exceptions\Error
     */

    public function getDashlet(): array
    {
        $types = [ 
            ['name' => 'exportErrorDuring24Hours', 'interval' => 1],
            ['name' => 'exportErrorDuring7Days', 'interval' => 7],
            ['name' => 'exportErrorDuring28Days', 'interval' => 28]
        ];

       foreach ($types as $type) {
            $data = $this->getExportData($type['interval']);
            $list[] = [
                'id'        => $this->getInjection('language')->translate($type['name']),
                'name'        => $this->getInjection('language')->translate($type['name']),
                'feeds'      => $data[0]['feeds'],
                'jobs'      => $data[0]['jobs'],
                'interval'     => $type['interval']
            ];
        }

        return ['total' => count($list), 'list' => $list];
    }

    protected function getExportData(int $interval): array
    {
        $query = "SELECT COUNT(*) AS jobs, COUNT(DISTINCT ef.id) as feeds
            FROM `export_feed` ef
            JOIN export_job ej ON ej.export_feed_id = ef.id
            WHERE ej.state = 'Failed'
            AND ej.start >= DATE_SUB(NOW(), INTERVAL $interval DAY)";

        return $this->getPDO()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
    }
}
