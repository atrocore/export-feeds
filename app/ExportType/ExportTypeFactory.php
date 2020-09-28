<?php

namespace Export\ExportType;

use Espo\Core\Exceptions\Error;
use Treo\Core\Utils\Metadata;
use Treo\Traits\ContainerTrait;

/**
 * ExportType factory
 *
 * @author r.ratsun@treolabs.com
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
