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
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Metadata;
use Espo\Services\Record;

/**
 * Class Base
 */
class Base
{
    public const DELIMITER = '|';

    protected Container $container;

    private array $services = [];

    private array $entityItem = [];

    private array $linkedEntities = [];

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
        $emptyValue = $configuration['emptyValue'];
        $nullValue = $configuration['nullValue'];
        $fieldDelimiterForRelation = $configuration['fieldDelimiterForRelation'];

        // get field type
        $type = (string)$this->getMetadata()->get(['entityDefs', $entity, 'fields', $field, 'type'], 'varchar');

        switch ($type) {
            case 'asset':
            case 'link':
                $result[$column] = $nullValue;

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
                                $foreign = $this->getEntity((string)$foreignEntity, $linkId);
                            } catch (\Throwable $e) {
                                $GLOBALS['log']->error('Export. Can not get foreign entity: ' . $e->getMessage());
                            }
                        }

                        if (!empty($foreign)) {
                            $foreignData = $foreign->toArray();

                            $fieldResult = [];
                            foreach ($exportBy as $v) {
                                $foreignType = (string)$this->getMetadata()->get(['entityDefs', $foreignEntity, 'fields', $v, 'type'], 'varchar');
                                $fieldResult[] = $this->prepareSimpleType($foreignType, $foreignData, $v, $configuration);
                            }

                            if (!empty($fieldResult)) {
                                $result[$column] = implode($fieldDelimiterForRelation, self::escapeValues($fieldResult, $fieldDelimiterForRelation));
                            }
                        } else {
                            $result[$column] = $emptyValue;
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
                            $result[$column] = implode($fieldDelimiterForRelation, self::escapeValues($fieldResult, $fieldDelimiterForRelation));
                        }
                    }
                }
                break;
            case 'linkMultiple':
                $params = [];
                if (!empty($configuration['channelId'])) {
                    $params['exportByChannelId'] = $configuration['channelId'];
                }

                try {
                    $foreignResult = $this->findLinkedEntities($entity, $record['id'], $field, $params);
                } catch (\Throwable $e) {
                    $GLOBALS['log']->error('Export. Can not get foreign entities: ' . $e->getMessage());
                }

                if (empty($configuration['exportIntoSeparateColumns'])) {
                    $result[$column] = $nullValue;
                }

                if (!empty($foreignResult['total'])) {
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
                            $fieldResult[] = $this->prepareSimpleType($foreignType, $foreignData, $v, $configuration);
                        }
                        $links[] = implode($fieldDelimiterForRelation, self::escapeValues($fieldResult, $fieldDelimiterForRelation));
                    }

                    if (!empty($configuration['exportIntoSeparateColumns'])) {
                        foreach ($links as $k => $link) {
                            $columnName = $column . '_' . ($k + 1);
                            $result[$columnName] = self::escapeValue($link, $delimiter);
                        }
                    } else {
                        $result[$column] = implode($delimiter, self::escapeValues($links, $delimiter));
                    }
                }
                break;
            default:
                $result[$column] = $this->prepareSimpleType($type, $record, $field, $configuration);
        }

        return $result;
    }

    public function getColumnLabel(string $colName, array $configuration, int $num): string
    {
        if (empty($colName)) {
            $entity = $configuration['feed']['data']['entity'];
            $field = $configuration['feed']['data']['configuration'][$num]['field'];

            $fieldData = $this->getMetadata()->get(['entityDefs', $entity, 'fields', $field]);

            if (empty($fieldData['multilangLocale'])) {
                throw new Error('Locale field expected.');
            }

            throw new BadRequest(sprintf($this->translate('noFieldLabel', 'exceptions', 'ExportFeed'), $fieldData['multilangField'], $fieldData['multilangLocale']));
        }

        return $colName;
    }

    /**
     * @param string $type
     * @param array  $record
     * @param string $field
     * @param array  $configuration
     *
     * @return mixed
     */
    protected function prepareSimpleType(string $type, array $record, string $field, array $configuration)
    {
        $delimiter = $configuration['delimiter'];
        $emptyValue = $configuration['emptyValue'];
        $nullValue = $configuration['nullValue'];
        $decimalMark = $configuration['decimalMark'];
        $thousandSeparator = $configuration['thousandSeparator'];

        switch ($type) {
            case 'array':
            case 'arrayMultiLang':
            case 'multiEnum':
            case 'multiEnumMultiLang':
                $result = $nullValue;
                if (isset($record[$field])) {
                    if (empty($record[$field])) {
                        $result = $record[$field] === null ? $nullValue : $emptyValue;
                    } else {
                        if (is_array($record[$field])) {
                            $result = implode($delimiter, self::escapeValues($record[$field], $delimiter));
                        }
                    }
                }
                break;
            case 'currency':
                $result = $nullValue;
                if (isset($record[$field])) {
                    if (empty($record[$field]) && $record[$field] !== '0' && $record[$field] !== 0) {
                        $result = $record[$field] === null ? $nullValue : $emptyValue;
                    } else {
                        $result = $this->floatToNumber((float)$record[$field], $decimalMark, $thousandSeparator) . ' ' . $record[$field . 'Currency'];
                    }
                }
                break;
            case 'unit':
                $result = $nullValue;
                if (isset($record[$field])) {
                    if (empty($record[$field]) && $record[$field] !== '0' && $record[$field] !== 0) {
                        $result = $record[$field] === null ? $nullValue : $emptyValue;
                    } else {
                        $result = $this->floatToNumber((float)$record[$field], $decimalMark, $thousandSeparator) . ' ' . $record[$field . 'Unit'];
                    }
                }
                break;
            case 'image':
            case 'asset':
                $result = $nullValue;
                $field = $field . 'Id';
                if (isset($record[$field])) {
                    if (empty($record[$field])) {
                        $result = $record[$field] === null ? $nullValue : $emptyValue;
                    } else {
                        if (!empty($attachment = $this->getEntity('Attachment', $record[$field]))) {
                            $result = $attachment->get('url');
                        } else {
                            $result = $emptyValue;
                        }
                    }
                }
                break;
            case 'link':
                $result = $nullValue;
                $field = $field . 'Id';
                if (isset($record[$field])) {
                    if (empty($record[$field])) {
                        $result = $record[$field] === null ? $nullValue : $emptyValue;
                    } else {
                        $result = $record[$field];
                    }
                }
                break;
            case 'linkMultiple':
                $result = $nullValue;
                break;
            case 'int':
                $result = $nullValue;
                if (isset($record[$field])) {
                    if (empty($record[$field]) && $record[$field] !== '0' && $record[$field] !== 0) {
                        $result = $record[$field] === null ? $nullValue : $emptyValue;
                    } else {
                        $result = number_format((float)$record[$field], 0, $decimalMark, $thousandSeparator);
                    }
                }
                break;
            case 'float':
                $result = $nullValue;
                if (isset($record[$field])) {
                    if (empty($record[$field]) && $record[$field] !== '0' && $record[$field] !== 0) {
                        $result = $record[$field] === null ? $nullValue : $emptyValue;
                    } else {
                        $result = $this->floatToNumber((float)$record[$field], $decimalMark, $thousandSeparator);
                    }
                }
                break;
            case 'bool':
                $result = !empty($record[$field]) ? 'TRUE' : 'FALSE';
                break;
            default:
                $result = $nullValue;
                if (isset($record[$field])) {
                    if (empty($record[$field])) {
                        $result = $record[$field] === null ? $nullValue : $emptyValue;
                    } else {
                        $result = $record[$field];
                    }
                }
        }

        return $result;
    }

    protected function floatToNumber(float $value, $decimalMark, $thousandSeparator): string
    {
        return rtrim(rtrim(number_format($value, 3, $decimalMark, $thousandSeparator), '0'), $decimalMark);
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

    protected function translate(string $key, string $tab, string $scope): string
    {
        return $this->container->get('language')->translate($key, $tab, $scope);
    }

    protected static function escapeValues(array $values, string $delimiter): array
    {
        foreach ($values as $k => $value) {
            $values[$k] = self::escapeValue($value, $delimiter);
        }

        return $values;
    }

    /**
     * @param mixed  $value
     * @param string $delimiter
     *
     * @return mixed
     */
    protected static function escapeValue($value, string $delimiter)
    {
        if (!is_string($value)) {
            return $value;
        }

        return str_replace($delimiter, '\\' . $delimiter, str_replace('\\' . $delimiter, $delimiter, $value));
    }

    protected function getEntity(string $scope, string $id)
    {
        if (!isset($this->entityItem[$scope][$id])) {
            $this->entityItem[$scope][$id] = $this->getService($scope)->getEntity($id);
        }

        return $this->entityItem[$scope][$id];
    }

    protected function findLinkedEntities(string $scope, string $id, string $field, array $params)
    {
        $key = "{$id}_{$field}_" . implode('-', $params);

        if (!isset($this->linkedEntities[$key])) {
            $this->linkedEntities[$key] = $this->getService($scope)->findLinkedEntities($id, $field, $params);
        }

        return $this->linkedEntities[$key];
    }
}
