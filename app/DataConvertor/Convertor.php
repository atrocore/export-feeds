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

class Convertor
{
    public const DELIMITER = '|';

    protected Container $container;

    private array $services = [];

    private array $entityItem = [];

    private array $linkedEntities = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function convert(array $record, array $configuration): array
    {
        $method = 'convert' . ucfirst($configuration['field']);
        if (method_exists($this, $method)) {
            return $this->$method($record, $configuration);
        }

        $type = $this->getMetadata()->get(['entityDefs', $configuration['entity'], 'fields', $configuration['field'], 'type'], 'varchar');

        $result = $this->convertType($type, $record, $configuration);

        return $result;
    }

    public function convertType(string $type, array $record, array $configuration): array
    {
        $result = [];

        $fieldConverterClass = '\Export\FieldConverters\\' . ucfirst($type) . 'Type';
        if (!class_exists($fieldConverterClass) || !is_a($fieldConverterClass, \Export\FieldConverters\AbstractType::class, true)) {
            $fieldConverterClass = '\Export\FieldConverters\VarcharType';
        }

        (new $fieldConverterClass($this))->convert($result, $record, $configuration);

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

    public function getEntity(string $scope, string $id)
    {
        if (!isset($this->entityItem[$scope][$id])) {
            $this->entityItem[$scope][$id] = $this->getService($scope)->getEntity($id);
        }

        return $this->entityItem[$scope][$id];
    }

    public function findLinkedEntities(string $scope, string $id, string $field, array $params)
    {
        $key = "{$id}_{$field}_" . implode('-', $params);

        if (!isset($this->linkedEntities[$key])) {
            $this->linkedEntities[$key] = $this->getService($scope)->findLinkedEntities($id, $field, $params);
        }

        return $this->linkedEntities[$key];
    }

    protected function floatToNumber(float $value, $decimalMark, $thousandSeparator): string
    {
        return rtrim(rtrim(number_format($value, 3, $decimalMark, $thousandSeparator), '0'), $decimalMark);
    }

    public function getMetadata(): Metadata
    {
        return $this->container->get('metadata');
    }

    public function getService(string $serviceName): Record
    {
        if (!isset($this->services[$serviceName])) {
            $this->services[$serviceName] = $this->container->get('serviceFactory')->create($serviceName);
        }

        return $this->services[$serviceName];
    }

    public function translate(string $key, string $tab, string $scope): string
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
}
