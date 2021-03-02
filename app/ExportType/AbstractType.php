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

declare(strict_types=1);

namespace Export\ExportType;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Attachment;
use Espo\ORM\EntityManager;
use Espo\Core\Container;

/**
 * Class AbstractType
 */
abstract class AbstractType
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $data;

    /**
     * AbstractType constructor.
     *
     * @param Container $container
     * @param array     $data
     */
    public function __construct(Container $container, array $data)
    {
        $this->container = $container;
        $this->data = $data;
    }

    /**
     * @return Attachment
     */
    abstract public function export(): Attachment;

    /**
     * @return EntityManager
     */
    protected function getEntityManager(): EntityManager
    {
        return $this->container->get('entityManager');
    }

    /**
     * @return Config
     */
    protected function getConfig(): Config
    {
        return $this->container->get('config');
    }

    /**
     * @return Metadata
     */
    protected function getMetadata(): Metadata
    {
        return $this->container->get('metadata');
    }

    /**
     * @param string $name
     *
     * @return \Espo\Core\SelectManagers\Base
     */
    protected function getSelectManager(string $name): \Espo\Core\SelectManagers\Base
    {
        return $this->container->get('selectManagerFactory')->create($name);
    }
}
