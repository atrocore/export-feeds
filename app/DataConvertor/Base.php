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

namespace Export\DataConvertor;

use Espo\Core\Container;
use Espo\Core\Utils\Metadata;
use Espo\Services\Record;

/**
 * Class Base
 */
class Base
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    private $services = [];

    /**
     * Base constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $record
     * @param array $configuration
     *
     * @return array
     */
    public function convert(array $record, array $configuration): array
    {
        $field = $configuration['field'];

        // delegate if it needs
        $method = 'convert' . ucfirst($field);
        if (method_exists($this, $method)) {
            return $this->$method($record, $configuration);
        }

        $result = [];

        $entity = $configuration['entity'];
        $column = $configuration['column'];
        $delimiter = $configuration['delimiter'];

        // get field type
        $type = (string)$this->getMetadata()->get(['entityDefs', $entity, 'fields', $field, 'type'], 'varchar');

        switch ($type) {
            case 'link':
                $result[$column] = null;

                $linkId = $record[$field . 'Id'];

                if (!empty($linkId)) {
                    $exportBy = isset($configuration['exportBy']) ? $configuration['exportBy'] : ['id'];

                    $needToCallForeignEntity = false;
                    foreach ($exportBy as $v) {
                        if (!in_array($v, ['id', 'name'])) {
                            $needToCallForeignEntity = true;
                            break 1;
                        }
                    }

                    if ($needToCallForeignEntity) {
                        $foreignEntity = $this->getMetadata()->get(['entityDefs', $entity, 'links', $field, 'entity']);
                        if (!empty($foreignEntity)) {
                            try {
                                $foreign = $this->getService((string)$foreignEntity)->getEntity($linkId);
                            } catch (\Throwable $e) {
                                $GLOBALS['log']->error('Export. Can not get foreign entity: ' . $e->getMessage());
                            }
                        }

                        if (!empty($foreign)) {
                            $foreignData = $foreign->toArray();

                            $fieldResult = [];
                            foreach ($exportBy as $v) {
                                $foreignType = (string)$this->getMetadata()->get(['entityDefs', $foreignEntity, 'fields', $v, 'type'], 'varchar');
                                $fieldResult[] = $this->prepareSimpleType($foreignType, $foreignData, $v, $delimiter);
                            }

                            if (!empty($fieldResult)) {
                                $result[$column] = implode('|', $fieldResult);
                            }
                        }
                    } else {
                        $fieldResult = [];
                        foreach ($exportBy as $v) {
                            $key = $field . ucfirst($v);
                            if (isset($record[$key])) {
                                $fieldResult[] = $record[$key];
                            }
                        }
                        if (!empty($fieldResult)) {
                            $result[$column] = implode('|', $fieldResult);
                        }
                    }
                }
                break;
            case 'linkMultiple':
                try {
                    $foreignResult = $this->getService($entity)->findLinkedEntities($record['id'], $field, []);
                } catch (\Throwable $e) {
                    $GLOBALS['log']->error('Export. Can not get foreign entities: ' . $e->getMessage());
                }

                if (!empty($foreignResult)) {
                    $foreignEntity = $this->getMetadata()->get(['entityDefs', $entity, 'links', $field, 'entity']);

                    if (isset($foreignResult['collection'])) {
                        $foreignList = $foreignResult['collection']->toArray();
                    } else {
                        $foreignList = $foreignResult['list'];
                    }

                    $exportBy = isset($configuration['exportBy']) ? $configuration['exportBy'] : ['id'];

                    $links = [];
                    foreach ($foreignList as $foreignData) {
                        $fieldResult = [];
                        foreach ($exportBy as $v) {
                            $foreignType = (string)$this->getMetadata()->get(['entityDefs', $foreignEntity, 'fields', $v, 'type'], 'varchar');
                            $fieldResult[] = $this->prepareSimpleType($foreignType, $foreignData, $v, $delimiter);
                        }
                        $links[] = implode('|', $fieldResult);
                    }

                    if (!empty($configuration['exportIntoSeparateColumns'])) {
                        foreach ($links as $k => $link) {
                            $columnName = $column . ' ' . ($k + 1);
                            $result[$columnName] = $link;
                        }
                    } else {
                        $result[$column] = implode($delimiter, $links);
                    }
                }
                break;
            default:
                $result[$column] = $this->prepareSimpleType($type, $record, $field, $configuration['delimiter']);
        }

        return $result;
    }

    /**
     * @param array $result
     * @param array $configuration
     *
     * @return array
     */
    public function prepareResult(array $result, array $configuration): array
    {
        return $result;
    }

    /**
     * @param string $type
     * @param array  $record
     * @param string $field
     * @param string $delimiter
     *
     * @return mixed
     */
    protected function prepareSimpleType(string $type, array $record, string $field, string $delimiter)
    {
        switch ($type) {
            case 'array':
            case 'arrayMultiLang':
            case 'multiEnum':
            case 'multiEnumMultiLang':
                if (!empty($record[$field]) && !empty($delimiter)) {
                    $result = implode($delimiter, $record[$field]);
                } else {
                    $result = null;
                }
                break;
            case 'currency':
                $result = $record[$field] . ' ' . $record[$field . 'Currency'];
                break;
            case 'unit':
                $result = $record[$field] . ' ' . $record[$field . 'Unit'];
                break;
            case 'link':
                $result = $record[$field] . 'Id';
                break;
            case 'linkMultiple':
                $result = null;
                break;
            default:
                $result = $record[$field];
        }

        return $result;
    }

    /**
     * @return Metadata
     */
    protected function getMetadata(): Metadata
    {
        return $this->container->get('metadata');
    }

    /**
     * @param string $serviceName
     *
     * @return Record
     */
    protected function getService(string $serviceName): Record
    {
        if (!isset($this->services[$serviceName])) {
            $this->services[$serviceName] = $this->container->get('serviceFactory')->create($serviceName);
        }

        return $this->services[$serviceName];
    }
}
