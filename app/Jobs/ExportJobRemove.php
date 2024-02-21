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

namespace Export\Jobs;

use Espo\Core\Jobs\Base;

class ExportJobRemove extends Base
{
    public function run($data, $targetId, $targetType, $scheduledJobId): bool
    {
        $scheduledJob = $this->getEntityManager()->getEntity('ScheduledJob', $scheduledJobId);
        if (empty($scheduledJob)) {
            return true;
        }

        $days = $scheduledJob->get('maximumDaysForJobExist');
        if (!is_integer($days)) {
            $days = 0;
        }

        $this->getServiceFactory()->create('ExportJob')->deleteOld($days);

        return true;
    }
}
