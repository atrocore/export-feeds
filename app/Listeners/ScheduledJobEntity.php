<?php

namespace Export\Listeners;

use Atro\Core\EventManager\Event;

class ScheduledJobEntity extends \Atro\Listeners\ScheduledJobEntity
{
    public function afterCreateJobsFromScheduledJobs(Event $event): void
    {
        if ($this->getConfig()->get('exportJobsMaxDays') !== 0) {
            $this->createJob('Delete Export Jobs', '30 0 * * 0', 'ExportJob', 'deleteOld');
        }
    }
}