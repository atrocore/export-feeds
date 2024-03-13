<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore GmbH (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
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
                'feeds'      => $data['feeds'],
                'jobs'      => $data['jobs'],
                'interval'     => $type['interval']
            ];
        }

        return ['total' => count($list), 'list' => $list];
    }

    protected function getExportData(int $interval): array
    {
        $connection = $this->getEntityManager()->getConnection();

        $res = $connection->createQueryBuilder()
            ->select('COUNT(ej.id) AS jobs, COUNT(DISTINCT ef.id) as feeds')
            ->from($connection->quoteIdentifier('export_feed'), 'ef')
            ->innerJoin('ef', $connection->quoteIdentifier('export_job'), 'ej', 'ej.export_feed_id = ef.id')
            ->where('ej.state = :state')
            ->andWhere('ej.start >= :start')
            ->setParameter('state', 'Failed')
            ->setParameter('start', (new \DateTime())->modify("-{$interval} days")->format('Y-m-d H:i:s'))
            ->fetchAssociative();

        return empty($res) ? ['jobs' => 0, 'feeds' => 0] : $res;
    }
}
