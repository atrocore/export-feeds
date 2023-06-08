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

use Espo\Core\SelectManagers\Base;

class ExportJob extends Base
{
    protected function accessOnlyOwn(&$result)
    {
    }

    protected function boolFilterOnlyExportFailed24Hours(array &$result): void
    {
        $result['whereClause'][] = [
            'id' => $this->getFailedExportJobFilteredIds(1)
        ];
    }

    protected function boolFilterOnlyExportFailed7Days(array &$result): void
    {
        $result['whereClause'][] = [
            'id' => $this->getFailedExportJobFilteredIds(7)
        ];
    }

    protected function boolFilterOnlyExportFailed28Days(array &$result): void
    {
        $result['whereClause'][] = [
            'id' => $this->getFailedExportJobFilteredIds(28)
        ];
    }

    protected function getFailedExportJobFilteredIds(int $interval): array
    {
        $query = "SELECT id
            FROM `export_job`
            WHERE state = 'Failed'
            AND start >= DATE_SUB(NOW(), INTERVAL $interval DAY)";

        return array_column(
            $this->getEntityManager()->getPDO()->query($query)->fetchAll(\PDO::FETCH_ASSOC),
            'id'
        );
    }
}
