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
 *
 * This software is not allowed to be used in Russia and Belarus.
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
