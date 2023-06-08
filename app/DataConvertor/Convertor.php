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
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;
use Espo\Services\Record;
use Export\Core\ValueModifier;

class Convertor
{
    protected Container $container;

    protected array $attributes = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function convert(array $record, array $configuration): array
    {
        if ($configuration['type'] == 'Fixed value' && isset($configuration['fixedValue'])) {
            $column = $configuration['column'];

            return [$column => (string)$configuration['fixedValue']];
        }

        $fieldDefs = $this->getMetadata()->get(['entityDefs', $configuration['entity'], 'fields', $configuration['field']]);
        $type = $fieldDefs['type'] ?? 'varchar';
        if (!empty($fieldDefs['unitField'])) {
            $type = 'unit';
        }

        return $this->convertType($type, $record, $configuration);
    }

    public function convertType(string $type, array $record, array $configuration): array
    {
        $result = [];

        $fieldConverterClass = '\Export\FieldConverters\\' . ucfirst($type) . 'Type';
        if (!class_exists($fieldConverterClass) || !is_a($fieldConverterClass, \Export\FieldConverters\AbstractType::class, true)) {
            $fieldConverterClass = '\Export\FieldConverters\VarcharType';
        }

        $fieldConverter = new $fieldConverterClass($this);
        $fieldConverter->convertToString($result, $record, $configuration);

        return $result;
    }

    public function getEntity(string $scope, string $id)
    {
        return $this->getService($scope)->getEntity($id);
    }

    public function findLinkedEntities(string $scope, string $id, string $field, array $params)
    {
        return $this->getService($scope)->findLinkedEntities($id, $field, $params);
    }

    public function getMetadata(): Metadata
    {
        return $this->container->get('metadata');
    }

    public function getConfig(): Config
    {
        return $this->container->get('config');
    }

    public function getService(string $serviceName): Record
    {
        return $this->container->get('serviceFactory')->create($serviceName);
    }

    public function translate(string $key, string $tab, string $scope): string
    {
        return $this->container->get('language')->translate($key, $tab, $scope);
    }

    public function getValueModifier(): ValueModifier
    {
        return $this->container->get(ValueModifier::class);
    }

    public function getAttributeById(string $attributeId): ?Entity
    {
        if (!isset($this->attributes[$attributeId])) {
            $this->attributes[$attributeId] = $this->container->get('entityManager')->getEntity('Attribute', $attributeId);
        }

        return $this->attributes[$attributeId];
    }

    public function getTypeForAttribute(string $attributeId, ?string $attributeValue): string
    {
        $attribute = $this->getAttributeById($attributeId);
        $type = $attribute->get('type');

        if ($attributeValue == null) {
            $attributeValue = 'value';
        }

        if ($attributeValue === 'valueUnitId') {
            return 'unit';
        }

        if ($type === 'rangeInt') {
            return 'int';
        }

        if ($type === 'rangeFloat') {
            return 'float';
        }

        return $type;
    }
}
