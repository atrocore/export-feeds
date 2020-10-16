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

declare(strict_types=1);

namespace Export\ExportType;

use Espo\Core\SelectManagers\Base as SelectManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Json;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;
use Treo\Core\EventManager\Event;
use Treo\Traits\ContainerTrait;

/**
 * Abstract export type
 */
abstract class AbstractType
{
    use ContainerTrait;

    /**
     * @var array
     */
    protected $feed;

    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var array
     */
    protected $query = [];

    /**
     * Get export data
     *
     * @param array $query
     *
     * @return array
     */
    abstract public function getData(): array;

    /**
     * Get export data count
     *
     * @return int
     */
    abstract public function getCount(): int;

    /**
     * Set feed
     *
     * @param array $feed
     *
     * @return AbstractType
     */
    public function setFeed(array $feed): AbstractType
    {
        $this->feed = $feed;

        return $this;
    }

    /**
     * Set offset
     *
     * @param int $offset
     *
     * @return AbstractType
     */
    public function setOffset(int $offset): AbstractType
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Set query
     *
     * @param array $query
     *
     * @return AbstractType
     */
    public function setQuery(array $query): AbstractType
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get query
     *
     * @return array
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * Get feed
     *
     * @return array
     */
    protected function getFeed(): array
    {
        return $this->feed;
    }

    /**
     * Get offset
     *
     * @return int
     */
    protected function getOffset(): int
    {
        return $this->offset;
    }


    /**
     * Get entity manager
     *
     * @return EntityManager
     */
    protected function getEntityManager(): EntityManager
    {
        return $this->getContainer()->get('entityManager');
    }

    /**
     * Get config
     *
     * @return Config
     */
    protected function getConfig(): Config
    {
        return $this->getContainer()->get('config');
    }

    /**
     * Get SelectManager
     *
     * @param string $name
     *
     * @return SelectManager
     */
    protected function getSelectManager(string $name): SelectManager
    {
        return $this->getContainer()->get('selectManagerFactory')->create($name);
    }

    /**
     * Get select params
     *
     * @return array
     */
    protected function getSelectParams(): array
    {
        return $this
            ->getSelectManager($this->getFeed()['data']['entity'])
            ->getSelectParams($this->getQuery(), true, true);
    }

    /**
     * Get entity field type
     *
     * @param string $entityType
     * @param string $field
     *
     * @return string|null
     */
    public function getFieldType(string $entityType, string $field): ?string
    {
        return $this
            ->getContainer()
            ->get('metadata')
            ->get(['entityDefs', $entityType, 'fields', $field, 'type']);
    }

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

        $delimiter = $this->getFeed()['data']['delimiter'];

        // check is field is 'id'
        if ($field == 'id') {
            return $entity->get('id');
        }

        // get field type
        $type = $this->getFieldType($entity->getEntityType(), $field);

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

    /**
     * @param string $target
     * @param string $action
     * @param array  $data
     *
     * @return array
     */
    protected function dispatch(string $target, string $action, array $data = []): Event
    {
        return $this->getContainer()->get('eventManager')->dispatch($target, $action, new Event($data));
    }
}
