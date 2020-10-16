<?php
/*
 * This file is part of premium software, which is NOT free.
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This Software is the property of AtroCore UG (haftungsbeschränkt) and is
 * protected by copyright law - it is NOT Freeware and can be used only in one
 * project under a proprietary license, which is delivered along with this program.
 * If not, see <https://atropim.com/eula> or <https://atrodam.com/eula>.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
 */

namespace Export\ExportType;

use Espo\Core\Exceptions\Error;
use Treo\Core\Utils\Metadata;
use Treo\Traits\ContainerTrait;

/**
 * ExportType factory
 */
class ExportTypeFactory
{
    use ContainerTrait;

    /**
     * @var array
     */
    private $exportConfig = null;

    /**
     * Create export type
     *
     * @param string $type
     *
     * @return AbstractType
     */
    public function create(string $type): AbstractType
    {
        // get config
        $config = $this->getExportConfig();

        if (!empty($className = $config['type'][$type])
            && in_array(AbstractType::class, class_parents($className))) {
            return (new $className())
                ->setContainer($this->getContainer());
        }

        throw new Error('No such export feed type');
    }

    /**
     * Get export config
     *
     * @return array
     */
    public function getExportConfig(): array
    {
        if (is_null($this->exportConfig)) {
            // get module list
            $this->exportConfig = $this->getMetadata()->get(['app', 'export']);
        }

        return $this->exportConfig;
    }

    /**
     * @return Metadata
     */
    protected function getMetadata(): Metadata
    {
        return $this->getContainer()->get('metadata');
    }
}
