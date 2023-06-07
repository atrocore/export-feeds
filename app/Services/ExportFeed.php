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

namespace Export\Services;

use Espo\Core\Exceptions;
use Espo\Core\Templates\Services\Base;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Util;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\Core\EventManager\Event;

class ExportFeed extends Base
{
    public function runExport(string $feedId, string $payload = null): bool
    {
        $exportFeed = $this->getEntity($feedId);
        if (empty($exportFeed)) {
            throw new Exceptions\NotFound();
        }

        $data = [
            'id'   => Util::generateId(),
            'feed' => $this->prepareFeedData($exportFeed)
        ];

        if (!empty($payload)) {
            $payload = @json_decode($payload, true);
            if (!empty($payload)) {
                foreach ($payload as $key => $value) {
                    $data['feed']['data']->{$key} = $value;
                }
            }
        }

        return $this->pushExport($data);
    }

    public function exportFile(\stdClass $requestData): bool
    {
        if (!property_exists($requestData, 'id')) {
            throw new Exceptions\NotFound();
        }

        $exportFeed = $this->getEntity($requestData->id);
        if (empty($exportFeed)) {
            throw new Exceptions\NotFound();
        }

        if (in_array($exportFeed->get('fileType'), ['csv', 'xlsx'])) {
            $configuratorItems = $exportFeed->get('configuratorItems');
            if (empty($configuratorItems) || count($configuratorItems) == 0) {
                throw new Exceptions\BadRequest($this->getInjection('language')->translate('noConfiguratorItems', 'exceptions', 'ExportFeed'));
            }
        }

        $this->getRepository()->removeInvalidConfiguratorItems($exportFeed->get('id'));

        $data = [
            'id'   => Util::generateId(),
            'feed' => $this->prepareFeedData($exportFeed)
        ];

        if (!empty($requestData->ignoreFilter)) {
            $data['feed']['data']->where = [];
        }

        if (!empty($requestData->entityFilterData)) {
            if (!empty($requestData->entityFilterData->byWhere)) {
                $data['feed']['data']->where = array_merge($data['feed']['data']->where, $requestData->entityFilterData->where);
            } else {
                $data['feed']['data']->where[] = [
                    'type'      => 'in',
                    'attribute' => 'id',
                    'value'     => $requestData->entityFilterData->ids
                ];
            }
        }

        return $this->pushExport($data);
    }

    public function addMissingFields(string $entityType, string $id): bool
    {
        switch ($entityType) {
            case 'Sheet':
                $sheet = $this->getEntityManager()->getEntity('Sheet', $id);
                $entity = $sheet->get('entity');
                $feed = $this->readEntity($sheet->get('exportFeedId'));
                break;
            default:
                $feed = $this->readEntity($id);
                $entity = $feed->get('entity');
        }

        $addedFields = [];
        foreach ($feed->get('configuratorItems') as $item) {
            $addedFields[] = $item->get('name') . '_' . $item->get('language');
        }

        $allFields = AbstractExportType::getAllFieldsConfiguration($entity, $this->getMetadata(), $this->getInjection('language'));

        foreach ($allFields as $row) {
            if (in_array($row['field'] . '_' . $row['language'], $addedFields)) {
                continue;
            }

            $item = $this->getEntityManager()->getEntity('ExportConfiguratorItem');
            $item->set('type', 'Field');
            $item->set('name', $row['field']);
            $item->set('language', $row['language']);
            $item->set('columnType', 'internal');
            $item->set(lcfirst($entityType) . 'Id', $id);
            if (isset($row['exportBy'])) {
                $item->set('exportBy', $row['exportBy']);
            }
            if (isset($row['exportIntoSeparateColumns'])) {
                $item->set('exportIntoSeparateColumns', !empty($row['exportIntoSeparateColumns']));
            }
            if (isset($row['offsetRelation'])) {
                $item->set('offsetRelation', $row['offsetRelation']);
            }
            if (isset($row['limitRelation'])) {
                $item->set('limitRelation', $row['limitRelation']);
            }
            if (isset($row['sortFieldRelation'])) {
                $item->set('sortFieldRelation', $row['sortFieldRelation']);
            }
            if (isset($row['sortOrderRelation'])) {
                $item->set('sortOrderRelation', $row['sortOrderRelation']);
            }
            if (isset($row['mask'])) {
                $item->set('mask', $row['mask']);
            }

            $this->getEntityManager()->saveEntity($item);
        }

        return true;
    }

    public function addAttributes(\stdClass $data): bool
    {
        switch ($data->entityType) {
            case 'Sheet':
                $sheet = $this->getEntityManager()->getEntity('Sheet', $data->id);
                $items = $sheet->get('configuratorItems');
                $relName = 'sheetId';
                $feed = $this->readEntity($sheet->get('exportFeedId'));
                break;
            default:
                $feed = $this->readEntity($data->id);
                $items = $feed->get('configuratorItems');
                $relName = 'exportFeedId';
        }

        $addedAttributes = [];
        if (!empty($items) && count($items) > 0) {
            foreach ($items as $item) {
                if (!empty($item->get('attributeId')) && $item->get('language') === 'main') {
                    $addedAttributes[] = $item->get('attributeId');
                }
            }
        }

        if (property_exists($data, 'ids')) {
            $params['where'] = [
                [
                    'type'      => 'equals',
                    'attribute' => 'id',
                    'value'     => $data->ids,
                ]
            ];
        }

        if (property_exists($data, 'where')) {
            $params['where'] = Json::decode(Json::encode($data->where), true);
        }

        if (!isset($params['where'])) {
            return false;
        }

        $exportConfiguratorItemService = $this->getInjection('serviceFactory')->create('ExportConfiguratorItem');

        /** @var \Pim\Repositories\Attribute $attributeRepository */
        $attributeRepository = $this->getEntityManager()->getRepository('Attribute');

        /** @var array $selectParams */
        $selectParams = $this->getSelectManager('Attribute')->getSelectParams($params, true, true);

        if ($attributeRepository->count($selectParams) > 2000) {
            throw new Exceptions\BadRequest($this->getInjection('language')->translate('toManyAttributesSelected', 'exceptions', 'ExportFeed'));
        }

        foreach ($attributeRepository->find($selectParams) as $attribute) {
            if (in_array($attribute->get('id'), $addedAttributes)) {
                continue;
            }

            $post = new \stdClass();
            $post->type = 'Attribute';
            $post->name = $attribute->get('name');
            if (empty($feed->get('language'))) {
                $post->language = 'main';
            }
            $post->$relName = $data->id;
            $post->attributeId = $attribute->get('id');

            if (!empty($feed->get('channelId'))) {
                $post->scope = 'Channel';
                $post->channelId = $feed->get('channelId');
                $post->channelName = $feed->get('channelName');
            }

            switch ($attribute->get('type')) {
                case 'currency':
                    $post->mask = "{{value}} {{currency}}";
                    break;
                case 'rangeInt':
                case 'rangeFloat':
                    $post->attributeValue = "valueFrom";
                    break;
                case 'extensibleEnum':
                case 'extensibleMultiEnum':
                    $post->exportBy = ["name"];
                    break;
            }

            $exportConfiguratorItemService->createEntity($post);
        }

        return true;
    }

    public function removeAllItems(string $entityType, string $id): bool
    {
        $this->getRepository()->removeConfiguratorItems($entityType, $id);

        return true;
    }

    public function readEntity($id)
    {
        $this->getRepository()->removeInvalidConfiguratorItems($id);

        return parent::readEntity($id);
    }

    public function findLinkedEntities($id, $link, $params)
    {
        if ($link === 'configuratorItems') {
            $this->getRepository()->removeInvalidConfiguratorItems($id);
        }

        return parent::findLinkedEntities($id, $link, $params);
    }

    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        foreach ($entity->getFeedFields() as $name => $value) {
            if (!in_array($name, ['fileType'])) {
                $entity->set($name, $value);
            }
        }

        if ($entity->get('type') === 'simple') {
            $entity->set('convertCollectionToString', true);
            $entity->set('convertRelationsToString', true);
        }

        $latestJob = $this->getEntityManager()
            ->getRepository('ExportJob')
            ->where([
                'exportFeedId' => $entity->id
            ])
            ->order('start', 'DESC')
            ->limit(1, 0)
            ->findOne();
        if (!empty($latestJob)) {
            $entity->set('lastStatus', $latestJob->get('state'));
            $entity->set('lastTime', $latestJob->get('start'));
        }

        $entity->set('replaceAttributeValues', !empty($entity->getFeedField('replaceAttributeValues')));
    }

    public function getExportTypeService(string $type): AbstractExportType
    {
        return $this->getInjection('serviceFactory')->create('ExportType' . ucfirst($type));
    }

    protected function init()
    {
        parent::init();

        $this->addDependency('queueManager');
        $this->addDependency('serviceFactory');
        $this->addDependency('language');
        $this->addDependency('user');
    }

    protected function beforeUpdateEntity(Entity $entity, $data)
    {
        parent::beforeUpdateEntity($entity, $data);

        foreach ($entity->getFeedFields() as $name => $value) {
            if (!$entity->has($name)) {
                $entity->set($name, $value);
            }
        }
    }

    public function prepareFeedDataConfiguration(Entity $sheet): array
    {
        $items = $this->getInjection('serviceFactory')->create($sheet->getEntityType())
            ->findLinkedEntities($sheet->get('id'), 'configuratorItems', ['maxSize' => \PHP_INT_MAX, 'sortBy' => 'sortOrder']);
        if (empty($items['total'])) {
            return [];
        }

        $feed = $sheet->getEntityType() === 'ExportFeed' ? $sheet : $sheet->get('exportFeed');

        $configuration = [];

        /** @var \Export\Services\ExportConfiguratorItem $eciService */
        $eciService = $this->getInjection('serviceFactory')->create('ExportConfiguratorItem');

        foreach ($items['collection'] as $item) {
            $row = [
                'columnType'                => $item->get('columnType'),
                'language'                  => $item->get('language'),
                'column'                    => $eciService->prepareColumnName($item),
                'template'                  => $feed->get('template'),
                'emptyValue'                => $feed->getFeedField('emptyValue'),
                'nullValue'                 => $feed->getFeedField('nullValue'),
                'markForNotLinkedAttribute' => $feed->getFeedField('markForNotLinkedAttribute'),
                'thousandSeparator'         => $feed->getFeedField('thousandSeparator'),
                'decimalMark'               => $feed->getFeedField('decimalMark'),
                'fieldDelimiterForRelation' => $feed->getFeedField('fieldDelimiterForRelation'),
                'convertCollectionToString' => !empty($feed->getFeedField('convertCollectionToString')),
                'convertRelationsToString'  => !empty($feed->getFeedField('convertRelationsToString')),
                'exportIntoSeparateColumns' => $item->get('exportIntoSeparateColumns'),
                'exportBy'                  => $item->get('exportBy'),
                'mask'                      => $item->get('mask'),
                'searchFilter'              => $item->get('searchFilter'),
                'filterField'               => $item->get('filterField'),
                'filterFieldValue'          => $item->get('filterFieldValue'),
                'offsetRelation'            => $item->get('offsetRelation'),
                'limitRelation'             => $item->get('limitRelation'),
                'sortFieldRelation'         => $item->get('sortFieldRelation'),
                'sortOrderRelation'         => $item->get('sortOrderRelation'),
                'valueModifier'             => $item->get('valueModifier'),
                'type'                      => $item->get('type'),
                'fixedValue'                => $item->get('fixedValue'),
                'zip'                       => !empty($item->get('zip')),
                'attributeValue'            => $item->get('attributeValue')
            ];
            if ($feed->get('type') === 'simple') {
                $row['convertCollectionToString'] = true;
                $row['convertRelationsToString'] = true;
            }

            if ($sheet->getEntityType() === 'ExportFeed') {
                $row['entity'] = $feed->getFeedField('entity');
                $row['sortOrderField'] = $feed->get('sortOrderField');
                $row['sortOrderDirection'] = $feed->get('sortOrderDirection');
            } else {
                $row['entity'] = $sheet->get('entity');
                $row['sortOrderField'] = $sheet->get('sortOrderField');
                $row['sortOrderDirection'] = $sheet->get('sortOrderDirection');
            }

            if ($item->get('type') === 'Field') {
                if ($item->get('name') !== 'id' && empty($this->getMetadata()->get(['entityDefs', $feed->getFeedField('entity'), 'fields', $item->get('name')]))) {
                    throw new Exceptions\BadRequest(sprintf($this->getInjection('language')->translate('noSuchField', 'exceptions', 'ExportFeed'), $item->get('name')));
                }
                $row['field'] = $item->get('name');
            }

            if ($item->get('type') === 'Attribute') {
                $attribute = $this->getEntityManager()->getEntity('Attribute', $item->get('attributeId'));
                if (empty($attribute)) {
                    throw new Exceptions\BadRequest(sprintf($this->getInjection('language')->translate('noSuchAttribute', 'exceptions', 'ExportFeed'), $item->get('name')));
                }

                $row['replaceAttributeValues'] = !empty($feed->getFeedField('replaceAttributeValues'));
                $row['attributeId'] = $attribute->get('id');
                $row['attributeName'] = $attribute->get('name');

                $row['scope'] = $item->get('scope');
                $row['channelId'] = null;
                $row['channelLocales'] = [];

                if ($row['scope'] === 'Channel') {
                    $row['channelId'] = $item->get('channelId');
                    if (!empty($channel = $item->get('channel'))) {
                        $row['channelLocales'] = $channel->get('locales');
                    }
                }
            }

            $configuration[] = $row;
        }

        return $configuration;
    }

    public function prepareFeedData(Entity $feed): array
    {
        $result = $feed->toArray();

        foreach ($feed->getFeedFields() as $name => $value) {
            $result[$name] = $value;
            $result['data']->$name = $value;
        }

        $result['fileType'] = $feed->get('fileType');

        if (!empty($feed->get('hasMultipleSheets'))) {
            $sheets = $this->findLinkedEntities($feed->get('id'), 'sheets', ['maxSize' => \PHP_INT_MAX, 'sortBy' => 'sortOrder']);
            foreach ($sheets['collection'] as $sheet) {
                if (empty($sheet->get('isActive'))) {
                    continue;
                }
                $result['sheets'][] = [
                    'name'               => $sheet->get('name'),
                    'entity'             => $sheet->get('entity'),
                    'sortOrderField'     => $sheet->get('sortOrderField'),
                    'sortOrderDirection' => $sheet->get('sortOrderDirection'),
                    'data'               => $sheet->get('data'),
                    'configuration'      => $this->prepareFeedDataConfiguration($sheet)
                ];
            }
        } else {
            $result['data']->configuration = Json::decode(Json::encode($this->prepareFeedDataConfiguration($feed)));
        }

        return $this
            ->getInjection('eventManager')
            ->dispatch('ExportFeedService', 'prepareFeedData', new Event(['feed' => $feed, 'result' => $result]))
            ->getArgument('result');
    }

    public function pushExport(array $data): bool
    {
        $data['offset'] = 0;
        $data['limit'] = empty($data['feed']['limit']) ? \PHP_INT_MAX : $data['feed']['limit'];

        $count = $this->getExportTypeService($data['feed']['type'])->getCount($data);

        if (!empty($data['feed']['separateJob'])) {
            $i = 1;
            while ($data['offset'] < $count) {
                $jobName = $data['feed']['name'];
                if ($count > $data['limit']) {
                    $jobName .= " ($i)";
                }
                $data['iteration'] = $i;
                $this->pushExportJob($jobName, $data);
                $data['offset'] = $data['offset'] + $data['limit'];
                $i++;
            }
        } else {
            $this->pushExportJob($data['feed']['name'], $data);
        }

        return true;
    }

    protected function pushExportJob(string $jobName, array $data): string
    {
        /** @var User $user */
        $user = $this->getInjection('user');

        $exportJob = $this->getEntityManager()->getEntity('ExportJob');
        $exportJob->id = Util::generateId();
        $exportJob->set('name', $jobName);
        $exportJob->set('exportFeedId', $data['feed']['id']);
        $exportJob->set('start', (new \DateTime())->format('Y-m-d H:i:s'));
        $exportJob->set('ownerUserId', $user->get('id'));
        $exportJob->set('assignedUserId', $user->get('id'));
        $exportJob->set('teamsIds', array_column($user->get('teams')->toArray(), 'id'));
        $exportJob->set('payload', $data);

        $data['exportJobId'] = $exportJob->get('id');

        $qmJobName = sprintf($this->getInjection('language')->translate('exportName', 'additionalTranslates', 'ExportFeed'), $jobName);

        $md5Hash = md5(json_encode($data['feed']) . $data['offset'] . $data['limit']);

        $priority = empty($data['feed']['priority']) ? 'Normal' : (string)$data['feed']['priority'];

        $this->getInjection('queueManager')->push($qmJobName, 'QueueManagerExport', $data, $priority, $md5Hash);
        $this->getEntityManager()->saveEntity($exportJob);

        return $exportJob->get('id');
    }

    /**
     * @param string $channelId
     *
     * @return Entity|null
     */
    protected function getChannel(string $channelId): ?Entity
    {
        return $this->getEntityManager()->getEntity('Channel', $channelId);
    }

    /**
     * @param Entity $channel
     *
     * @return array
     */
    protected function getChannelFeeds(Entity $channel): ?EntityCollection
    {
        if (!empty($feeds = $channel->get('exportFeeds'))) {
            foreach ($feeds as $feed) {
                if (!empty($feed->get('isActive')) && empty($feed->get('deleted'))) {
                    $result[] = $feed;
                }
            }
        }

        return (empty($result)) ? null : new EntityCollection($result);
    }

    protected function isEntityUpdated(Entity $entity, \stdClass $data): bool
    {
        return true;
    }

    protected function getFieldsThatConflict(Entity $entity, \stdClass $data): array
    {
        return [];
    }

    public function duplicateConfiguratorItems(Entity $entity, Entity $duplicatingEntity): void
    {
        if (empty($items = $duplicatingEntity->get('configuratorItems')) || count($items) === 0) {
            return;
        }

        foreach ($items as $item) {
            $data = $item->toArray();
            unset($data['id']);
            unset($data['createdAt']);
            $data['exportFeedId'] = $entity->get('id');

            $newItem = $this->getEntityManager()->getEntity('ExportConfiguratorItem');
            $newItem->set($data);
            $this->getEntityManager()->saveEntity($newItem);
        }
    }

    public function duplicateExportHttpHeaders(Entity $entity, Entity $duplicatingEntity): void
    {
        $headers = $duplicatingEntity->get('exportHttpHeaders');

        if (empty($headers) || count($headers) === 0) {
            return;
        }

        foreach ($headers as $header) {
            $data = $header->toArray();
            unset($data['id']);
            $data['exportFeedId'] = $entity->get('id');

            $newHeader = $this->getEntityManager()->getEntity('ExportHttpHeader');
            $newHeader->set($data);
            $this->getEntityManager()->saveEntity($newHeader);
        }
    }
}
