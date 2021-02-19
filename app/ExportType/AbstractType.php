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
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;
use Treo\Core\Container;

/**
 * Abstract export type
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
     * @return bool
     */
    abstract public function export(): bool;


    /**
     * Prepare entity field value
     *
     * @param Entity $entity
     * @param array  $params
     *
     * @return mixed
     */
    protected function prepareFieldValue(Entity $entity, array $params)
    {
        $result = null;

        // get field
        $field = isset($params['field']) ? $params['field'] : $params['name'];

        $delimiter = $this->data['feed']['data']['delimiter'];

        // check is field is 'id'
        if ($field == 'id') {
            return $entity->get('id');
        }

        // get field type
        $type = $this->getMetadata()->get(['entityDefs', $entity->getEntityType(), 'fields', $field, 'type']);

        // if field exist in export entity
        if (isset($type)) {
            // get field value
            $result = $entity->get($field);

            // check if empty value
            if (!empty($result)) {
                // check if field is link or linkMultiple
                if ($type == 'link' || $type == 'linkMultiple') {
                    // get export link field
                    if (isset($params['exportBy'])) {
                        $exportBy = $params['exportBy'];
                    } else {
                        $exportBy = 'id';
                    }

                    // prepare links values
                    if ($result instanceof Entity) {
                        if ($result->hasField($exportBy)) {
                            $result = $result->get($exportBy);
                        }
                    } elseif ($result instanceof EntityCollection) {
                        if (count($result) > 0) {
                            $values = [];

                            foreach ($result as $item) {
                                if ($item->hasField($exportBy)) {
                                    $values[] = $item->get($exportBy);
                                }
                            }

                            // save result as string with selected delimiter
                            $result = implode($delimiter, $values);
                        } else {
                            $result = null;
                        }
                    }
                } elseif (in_array($type, ['array', 'arrayMultiLang', 'multiEnum', 'multiEnumMultiLang'])) {
                    $result = implode($delimiter, Json::decode(Json::encode($entity->get($field)), true));
                }
            }
        }

        return $result;
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->container->get('entityManager');
    }

    protected function getConfig(): Config
    {
        return $this->container->get('config');
    }

    protected function getMetadata(): Metadata
    {
        return $this->container->get('metadata');
    }

    protected function getSelectManager(string $name): \Espo\Core\SelectManagers\Base
    {
        return $this->container->get('selectManagerFactory')->create($name);
    }
}
