<?php

declare(strict_types=1);

namespace Export\Loaders;

use Treo\Core\Loaders\Base;
use Export\ExportType\ExportTypeFactory;

/**
 * ExportTypeFactory loader
 *
 * @author r.ratsun@treolabs.com
 */
class ExportTypeFactoryLoader extends Base
{

    /**
     * Load ExportTypeFactory
     *
     * @return ExportTypeFactory
     */
    public function load()
    {
        return (new ExportTypeFactory())->setContainer($this->getContainer());
    }
}
