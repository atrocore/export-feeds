<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.md, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

namespace Export\SelectManagers;

use Espo\Core\SelectManagers\Base;

class ExportJob extends Base
{
    protected function access(&$result)
    {
        $exportFeeds = $this->getEntityManager()->getRepository('ExportFeed')
            ->select(['id'])
            ->find($this->createSelectManager('ExportFeed')->getSelectParams([], true, true));

        $result['whereClause'][] = ['OR' => ['exportFeedId' => array_column($exportFeeds->toArray(), 'id')]];
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
