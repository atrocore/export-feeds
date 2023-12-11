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
use Atro\Core\KeyValueStorages\StorageInterface;
use Espo\Core\Container;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;
use Espo\Services\Record;

class Convertor
{
    protected Container $container;
    public string $keyName = 'linked_entities_keys';

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

    public function findLinkedEntities(array $records, string $scope, string $id, string $field, array $params)
    {
        $relEntityType = $this->getMetadata()->get(['entityDefs', $scope, 'links', $field, 'entity']);

        $collection = new EntityCollection([], $relEntityType);

        // load to memory
        $this->loadToMemory($records, $scope, $field, $params);

        $linkedEntitiesKeys = $this->getMemoryStorage()->get($this->keyName) ?? [];

        if (!isset($linkedEntitiesKeys[$scope][$field])) {
            return ['collection' => $collection];
        }

        // find current record
        $record = null;
        foreach ($records as $v) {
            if ($id === $v['id']) {
                $record = $v;
                break;
            }
        }

        if (empty($record)) {
            throw new \Error("Cannot find $scope by id $id.");
        }

        $keySet = $this->getKeySet($scope, $field);

        $nearKey = $keySet['nearKey'] ?? $keySet['foreignKey'];

        $number = 0;

        foreach ($linkedEntitiesKeys[$scope][$field] as $key) {
            $relEntity = $this->getMemoryStorage()->get($key);
            if (property_exists($relEntity, '_relIds') && !empty($relEntity->_relIds)) {
                if (!in_array($record[$keySet['key']], $relEntity->_relIds)) {
                    continue;
                }
            } else {
                if ($relEntity->get($nearKey) !== $record[$keySet['key']]) {
                    continue;
                }
            }

            if (isset($params['offset']) && $number < $params['offset']) {
                $number++;
                continue;
            }

            if (isset($params['maxSize']) && $collection->count() >= $params['maxSize']) {
                break;
            }

            $collection->append($relEntity);
        }

        return ['collection' => $collection];
    }

    protected function loadToMemory(array $records, string $entityType, string $relationName, array $params): void
    {
        $linkedEntitiesKeys = $this->getMemoryStorage()->get($this->keyName) ?? [];
        if (isset($linkedEntitiesKeys[$entityType][$relationName])) {
            return;
        }

        $params['offset'] = 0;
        $params['maxSize'] = $this->getConfig()->get('exportMemoryItemsCount', 10000);

        $linkDefs = $this->getMetadata()->get(['entityDefs', $entityType, 'links', $relationName]);

        if (!isset($linkDefs['entity'])) {
            throw new \Error("Metadata error. No 'entity' parameter for '$relationName' relation.");
        }

        if ($linkDefs['type'] === 'belongsTo') {
            $params['where'][] = [
                'type'      => 'in',
                'attribute' => 'id',
                'value'     => array_column($records, lcfirst($linkDefs['entity']) . 'Id')
            ];
        } else {
            if (empty($linkDefs['foreign'])) {
                throw new \Error("Metadata error. No 'foreign' parameter for '$relationName' relation.");
            }
            $params['where'][] = [
                'type'      => 'linkedWith',
                'attribute' => $linkDefs['foreign'],
                'value'     => array_column($records, 'id')
            ];

            // load relation ids
            if ($linkDefs['type'] === 'hasMany' && !empty($linkDefs['relationName'])) {
                $keySet = $this->getKeySet($entityType, $relationName);
                $relationCollection = $this->getEntityManager()->getRepository(ucfirst($linkDefs['relationName']))
                    ->select(['id', $keySet['nearKey'], $keySet['distantKey']])
                    ->where([$keySet['nearKey'] => array_column($records, 'id')])
                    ->find();
                $relRecords = [];
                foreach ($relationCollection as $relEntity) {
                    $relRecords[$relEntity->get($keySet['distantKey'])][] = $relEntity->get($keySet['nearKey']);
                }
            }
        }

        $res = $this->getService($linkDefs['entity'])->findEntities($params);

        foreach ($res['collection'] as $re) {
            $re->_relIds = $relRecords[$re->get('id')] ?? null;
            $itemKey = $this->getEntityManager()->getRepository($re->getEntityType())->getCacheKey($re->get('id'));
            $this->getMemoryStorage()->set($itemKey, $re);
            $linkedEntitiesKeys[$entityType][$relationName][] = $itemKey;
        }
        $this->getMemoryStorage()->set($this->keyName, $linkedEntitiesKeys);
    }

    public function clearMemoryOfLoadedEntities(): void
    {
        $linkedEntitiesKeys = $this->getMemoryStorage()->get($this->keyName) ?? [];
        foreach ($linkedEntitiesKeys as $entityType => $relations) {
            foreach ($relations as $relation => $keys) {
                foreach ($keys as $key) {
                    $this->getMemoryStorage()->delete($key);
                }
            }
        }
        $this->getMemoryStorage()->delete($this->keyName);
    }

    public function getMemoryStorage(): StorageInterface
    {
        return $this->container->get('memoryStorage');
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

    public function getKeySet(string $entityType, string $link): array
    {
        $entityRepository = $this->getEntityManager()->getRepository($entityType);
        return $entityRepository->getMapper()->getKeys($entityRepository->get(), $link);
    }
}
