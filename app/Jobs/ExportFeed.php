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

declare(strict_types=1);

namespace Export\Jobs;

use Espo\Core\Jobs\Base;

class ExportFeed extends Base
{
    public function run($data, $targetId, $targetType, $scheduledJobId): bool
    {
        $scheduledJob = $this->getEntityManager()->getEntity('ScheduledJob', $scheduledJobId);
        if (empty($scheduledJob) || empty($exportFeedId = $scheduledJob->get('exportFeedId'))) {
            return true;
        }

        $requestData = new \stdClass();
        $requestData->id = $exportFeedId;

        $this->getServiceFactory()->create('ExportFeed')->exportFile($requestData);

        return true;
    }
}
