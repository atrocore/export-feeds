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

namespace Export\SelectManagers;

use Espo\Core\SelectManagers\Base;

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

    protected function boolFilterHasMultipleSheets(array &$result): void
    {
        $result['whereClause'][] = [
            'hasMultipleSheets' => true
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
