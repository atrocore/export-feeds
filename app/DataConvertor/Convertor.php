<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
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
        if ($configuration['type'] == 'Fixed value') {
            if (isset($configuration['fixedValue'])) {
                return [$configuration['column'] => (string)$configuration['fixedValue']];
            }
            return [$configuration['column'] => ""];
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

        if ($attributeValue === 'id') {
            return 'varchar';
        }

        if ($attributeValue === 'value' && in_array($type, ['int', 'float', 'rangeInt', 'rangeFloat', 'varchar'])) {
            return 'valueWithUnit';
        }

        if ($attributeValue === 'valueUnit') {
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
