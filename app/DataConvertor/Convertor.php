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

use Atro\Core\EventManager\Manager;
use Espo\Core\Container;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Services\Record;

class Convertor
{
    protected Container $container;
    private array $cache = [];

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

        if ($configuration['type'] === 'script') {
            $template = $configuration['script'] ?? '';
            $templateData = [
                'record'        => $record,
                'configuration' => $configuration
            ];
            $result[$configuration['column']] = $this->container->get('twig')->renderTemplate((string)$template, $templateData);
            return $result;
        }

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

    public function getEntityManager(): EntityManager
    {
        return $this->container->get('entityManager');
    }

    public function getEventManager(): Manager
    {
        return $this->container->get('eventManager');
    }

    public function translate(string $key, string $tab, string $scope): string
    {
        return $this->container->get('language')->translate($key, $tab, $scope);
    }

    public function getAttributeById(string $attributeId): ?Entity
    {
        return $this->getEntityManager()->getEntity('Attribute', $attributeId);
    }

    public function getTypeForAttribute(string $attributeType, ?string $attributeValue): string
    {
        if ($attributeValue == null) {
            $attributeValue = 'value';
        }

        if ($attributeValue === 'id') {
            return 'varchar';
        }

        if ($attributeValue === 'value' && in_array($attributeType, ['int', 'float', 'rangeInt', 'rangeFloat', 'varchar'])) {
            return 'valueWithUnit';
        }

        if ($attributeValue === 'valueUnit') {
            return 'unit';
        }

        if ($attributeType === 'rangeInt') {
            return 'int';
        }

        if ($attributeType === 'rangeFloat') {
            return 'float';
        }

        return $attributeType;
    }

    public function putCache(string $name, $value): void
    {
        $this->cache[$name] = $value;
    }

    public function getCache(string $name)
    {
        return $this->cache[$name] ?? null;
    }
}
