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
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

declare(strict_types=1);

namespace Export\DataConvertor;

use Espo\Core\Container;
use Espo\Core\Utils\Metadata;
use Espo\Services\Record;
use Export\Core\ValueModifier;

class Convertor
{
    protected Container $container;

    private array $services = [];

    private array $entityItem = [];

    private array $linkedEntities = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function convert(array $record, array $configuration, bool $toString = false): array
    {
        $type = $this->getMetadata()->get(['entityDefs', $configuration['entity'], 'fields', $configuration['field'], 'type'], 'varchar');

        return $this->convertType($type, $record, $configuration, $toString);
    }

    public function convertType(string $type, array $record, array $configuration, bool $toString = false): array
    {
        $result = [];

        $fieldConverterClass = '\Export\FieldConverters\\' . ucfirst($type) . 'Type';
        if (!class_exists($fieldConverterClass) || !is_a($fieldConverterClass, \Export\FieldConverters\AbstractType::class, true)) {
            $fieldConverterClass = '\Export\FieldConverters\VarcharType';
        }

        $fieldConverter = new $fieldConverterClass($this);

        if ($toString) {
            $fieldConverter->convertToString($result, $record, $configuration);
        } else {
            $fieldConverter->convert($result, $record, $configuration);
        }

        return $result;
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

    public function getValueModifier(): ValueModifier
    {
        return $this->container->get(ValueModifier::class);
    }
}
