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
use Export\ExportType\AbstractType;
use Export\ExportType\Simple;

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

        $allFields = Simple::getAllFieldsConfiguration($feed->get('entity'), $this->getMetadata(), $this->getInjection('language'));

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

        $attributes = $this
            ->getEntityManager()
            ->getRepository('Attribute')
            ->find($this->getSelectManager('Attribute')->getSelectParams($params, true, true));

        foreach ($attributes as $attribute) {
            if (in_array($attribute->get('id'), $addedAttributes)) {
                continue;
            }

            $item = $this->getEntityManager()->getEntity('ExportConfiguratorItem');
            $item->set('type', 'Attribute');
            $item->set('name', $attribute->get('name'));
            $item->set('locale', 'mainLocale');
            $item->set('exportFeedId', $feed->get('id'));
            $item->set('attributeId', $attribute->get('id'));
            $this->getEntityManager()->saveEntity($item);
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

    /**
     * Init
     */
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

        return $result;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function pushExport(array $data): bool
    {
        /** @var User $user */
        $user = $this->getInjection('user');

        $exportJob = $this->getEntityManager()->getEntity('ExportJob');
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

        $name = sprintf($this->getInjection('language')->translate('exportName', 'additionalTranslates', 'ExportFeed'), $data['feed']['name']);

        return $this
            ->getInjection('queueManager')
            ->push($name, 'QueueManagerExport', $data);
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
