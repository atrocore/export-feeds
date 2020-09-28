<?php

declare(strict_types=1);

namespace Export;

use Treo\Core\ModuleManager\AbstractModule;

/**
 * Class Module
 *
 * @author r.zablodskiy <r.zablodskiy@treolabs.com>
 */
class Module extends AbstractModule
{
    /**
     * @inheritdoc
     */
    public static function getLoadOrder(): int
    {
        return 5140;
    }
}
