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

declare(strict_types=1);

namespace Export\Services;

use Atro\Core\QueueManager;
use Espo\Core\Utils\Util;
use Espo\Entities\User;
use Espo\Services\QueueManagerBase;

class ExportJobCreator extends QueueManagerBase
{
    public function run(array $data = []): bool
    {
        $data['offset'] = 0;
        $data['limit'] = empty($data['feed']['limit']) ? \PHP_INT_MAX : $data['feed']['limit'];

        $count = $this->getExportFeedService()->getExportTypeService($data['feed']['type'])->getCount($data);

        if (!empty($data['feed']['separateJob']) && $count !== null) {
            $i = 1;
            while ($data['offset'] < $count) {
                $jobName = $data['feed']['name'];
                if ($count > $data['limit']) {
                    $jobName .= " ($i)";
                }
                $data['iteration'] = $i;
                $this->pushExportJob($jobName, $data);
                $data['offset'] = $data['offset'] + $data['limit'];
                $i++;
            }
        } else {
            $this->pushExportJob($data['feed']['name'], $data);
        }

        return true;
    }

    protected function pushExportJob(string $jobName, array $data): string
    {
        /** @var User $user */
        $user = $this->getInjection('user');

        $exportJob = $this->getEntityManager()->getEntity('ExportJob');
        $exportJob->id = Util::generateId();
        $exportJob->set('name', $jobName);
        $exportJob->set('exportFeedId', $data['feed']['id']);
        $exportJob->set('start', (new \DateTime())->format('Y-m-d H:i:s'));
        $exportJob->set('ownerUserId', $user->get('id'));
        $exportJob->set('assignedUserId', $user->get('id'));
        $exportJob->set('teamsIds', array_column($user->get('teams')->toArray(), 'id'));
        $exportJob->set('payload', $data);

        $data['exportJobId'] = $exportJob->get('id');

        $qmJobName = sprintf($this->translate('exportName', 'additionalTranslates', 'ExportFeed'), $jobName);

        $md5Hash = md5(json_encode($data['feed']) . $data['offset'] . $data['limit']);

        $priority = empty($data['feed']['priority']) ? 'Normal' : (string)$data['feed']['priority'];

        $qmId = $this->getQM()->createQueueItem($qmJobName, 'QueueManagerExport', $data, $priority, $md5Hash);

        $exportJob->set('queueItemId', $qmId);

        $this->getEntityManager()->saveEntity($exportJob);

        return $exportJob->get('id');
    }

    protected function getExportFeedService(): ExportFeed
    {
        return $this->getContainer()->get('serviceFactory')->create('ExportFeed');
    }

    protected function getQM(): QueueManager
    {
        return $this->getContainer()->get('queueManager');
    }
}
