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
use Treo\Core\EventManager\Event;

/**
 * ExportFeed service
 */
class ExportFeed extends Base
{
    /**
     * Export file
     *
     * @param \stdClass $requestData
     *
     * @return bool
     * @throws Exceptions\NotFound
     */
    public function exportFile(\stdClass $requestData): bool
    {
        if (empty($exportFeed = $this->getEntityManager()->getEntity('ExportFeed', $requestData->id))) {
            throw new Exceptions\NotFound();
        }

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

            $this->pushExport($data);
            return true;
        }

        if (!empty($requestData->exportByChannelId)) {
            $data['exportByChannelId'] = $requestData->exportByChannelId;
            $this->pushExport($data);
        } else {
            $channelsIds = array_column($exportFeed->get('channels')->toArray(), 'id');
            if (empty($channelsIds)) {
                $this->pushExport($data);
            } else {
                foreach ($channelsIds as $channelId) {
                    $data['exportByChannelId'] = $channelId;
                    $this->pushExport($data);
                }
            }
        }

        return true;
    }

    /**
     * Export all channel feeds
     *
     * @param string $channelId
     *
     * @return bool
     */
    public function exportChannel(string $channelId): bool
    {
        // prepare result
        $result = false;

        if (!empty($channel = $this->getChannel($channelId)) && !empty($feeds = $this->getChannelFeeds($channel))) {
            foreach ($feeds as $feed) {
                $requestData = new \stdClass();
                $requestData->id = $feed->get('id');
                $requestData->exportByChannelId = $channelId;

                try {
                    $this->exportFile($requestData);
                } catch (\Throwable $e) {
                    $GLOBALS['log']->error('Export Error: ' . $e->getMessage());
                }
            }
            $result = true;
        }

        return $result;
    }

    public function addMissingFields(string $feedId): bool
    {
        $feed = $this->readEntity($feedId);

        $addedFields = array_column($feed->get('configuratorItems')->toArray(), 'name');

        $allFields = AbstractExportType::getAllFieldsConfiguration($feed->get('entity'), $this->getMetadata(), $this->getInjection('language'));

        foreach ($allFields as $row) {
            if (in_array($row['field'], $addedFields)) {
                continue;
            }

            $item = $this->getEntityManager()->getEntity('ExportConfiguratorItem');
            $item->set('type', 'Field');
            $item->set('name', $row['field']);
            $item->set('exportFeedId', $feedId);
            if (isset($row['exportBy'])) {
                $item->set('exportBy', $row['exportBy']);
            }
            if (isset($row['exportIntoSeparateColumns'])) {
                $item->set('exportIntoSeparateColumns', !empty($row['exportIntoSeparateColumns']));
            }

            $this->getEntityManager()->saveEntity($item);
        }

        return true;
    }

    public function addAttributes(\stdClass $data): bool
    {
        $feed = $this->readEntity($data->exportFeedId);

        $addedAttributes = [];
        if (!empty($items = $feed->get('configuratorItems')) && count($items) > 0) {
            foreach ($items as $item) {
                if (!empty($item->get('attributeId')) && $item->get('locale') === 'mainLocale') {
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

        $attributes = $this
            ->getEntityManager()
            ->getRepository('Attribute')
            ->find($this->getSelectManager('Attribute')->getSelectParams($params, true, true));

        foreach ($attributes as $attribute) {
            if (in_array($attribute->get('id'), $addedAttributes)) {
                continue;
            }

            $post = new \stdClass();
            $post->type = 'Attribute';
            $post->name = $attribute->get('name');
            $post->locale = 'mainLocale';
            $post->exportFeedId = $feed->get('id');
            $post->exportFeedName = $feed->get('name');
            $post->attributeId = $attribute->get('id');
            $post->attributeName = $attribute->get('name');
            $post->addAllLocales = true;

            $exportConfiguratorItemService->createEntity($post);
        }

        return true;
    }

    public function removeAllItems(string $feedId): bool
    {
        $this->getRepository()->removeConfiguratorItems($feedId);

        return true;
    }

    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        foreach ($entity->getFeedFields() as $name => $value) {
            $entity->set($name, $value);
        }
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

    protected function prepareFeedData(Entity $feed): array
    {
        $result = $feed->toArray();

        foreach ($feed->getFeedFields() as $name => $value) {
            $result[$name] = $value;
            $result['data']->$name = $value;
        }

        $configuration = [];
        $items = $this->findLinkedEntities($feed->get('id'), 'configuratorItems', ['maxSize' => \PHP_INT_MAX, 'sortBy' => 'sortOrder']);
        if (!empty($items['total'])) {
            /** @var \Export\Services\ExportConfiguratorItem $eciService */
            $eciService = $this->getInjection('serviceFactory')->create('ExportConfiguratorItem');

            foreach ($items['collection'] as $item) {
                $row = [
                    'columnType'                => $item->get('columnType'),
                    'locale'                    => $item->get('locale'),
                    'column'                    => $eciService->prepareColumnName($item),
                    'entity'                    => $feed->getFeedField('entity'),
                    'emptyValue'                => $feed->getFeedField('emptyValue'),
                    'nullValue'                 => $feed->getFeedField('nullValue'),
                    'markForNotLinkedAttribute' => $feed->getFeedField('markForNotLinkedAttribute'),
                    'thousandSeparator'         => $feed->getFeedField('thousandSeparator'),
                    'decimalMark'               => $feed->getFeedField('decimalMark'),
                    'fieldDelimiterForRelation' => $feed->getFeedField('fieldDelimiterForRelation'),
                    'exportIntoSeparateColumns' => $item->get('exportIntoSeparateColumns'),
                    'exportBy'                  => $item->get('exportBy'),
                ];

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
                    $row['attributeId'] = $attribute->get('id');
                    $row['attributeName'] = $attribute->get('name');
                }

                $configuration[] = $row;
            }
        }

        $result['data']->configuration = Json::decode(Json::encode($configuration));

        return $this
            ->getInjection('eventManager')
            ->dispatch('ExportFeedService', 'prepareFeedData', new Event(['feed' => $feed, 'result' => $result]))
            ->getArgument('result');
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function pushExport(array $data): bool
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

    protected function pushExportJob(string $jobName, array $data): void
    {
        /** @var User $user */
        $user = $this->getInjection('user');

        $exportJob = $this->getEntityManager()->getEntity('ExportJob');
        $exportJob->set('name', $jobName);
        $exportJob->set('exportFeedId', $data['feed']['id']);
        $exportJob->set('start', (new \DateTime())->format('Y-m-d H:i:s'));
        $exportJob->set('ownerUserId', $user->get('id'));
        $exportJob->set('assignedUserId', $user->get('id'));
        $exportJob->set('teamsIds', array_column($user->get('teams')->toArray(), 'id'));

        if (!empty($data['exportByChannelId'])) {
            $exportJob->set('channelId', $data['exportByChannelId']);
        }

        $this->getEntityManager()->saveEntity($exportJob);

        $data['exportJobId'] = $exportJob->get('id');

        $qmJobName = sprintf($this->getInjection('language')->translate('exportName', 'additionalTranslates', 'ExportFeed'), $jobName);

        $this->getInjection('queueManager')->push($qmJobName, 'QueueManagerExport', $data);
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
}
