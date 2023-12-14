<?php

namespace Export;

use Atro\Core\ModuleManager\AfterInstallAfterDelete;
use Espo\Core\Utils\Config;

class Event extends AfterInstallAfterDelete
{
    public function afterInstall(): void
    {
        /** @var Config $config */
        $config = $this->getContainer()->get('config');
        $config->set('exportJobsMaxDays', 21);
        $config->save();
    }
}