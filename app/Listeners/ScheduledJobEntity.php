<?php

namespace Export\Listeners;

use Atro\Core\EventManager\Event;
use Atro\Listeners\AbstractListener;

class ScheduledJobEntity extends AbstractListener
{
    public function afterCreateJobsFromScheduledJobs(Event $event): void
    {
        if ($this->getConfig()->get('exportJobsMaxDays') !== 0) {
            $this->createJob('Delete Export Jobs', '30 0 * * 0', 'ExportJob', 'deleteOld');
        }
    }
}