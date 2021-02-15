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
