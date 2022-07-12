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

namespace Export\Services;

use Espo\Core\Exceptions;
use Espo\Core\Templates\Services\Base;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Util;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\Core\EventManager\Event;

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
     * @return string
     * @throws Exceptions\NotFound
     */
    public function exportFile(\stdClass $requestData): string
    {
        if (empty($exportFeed = $this->getEntityManager()->getEntity('ExportFeed', $requestData->id))) {
            throw new Exceptions\NotFound();
        }

        if (in_array($exportFeed->get('type'), $this->getMetadata()->get(['scopes', 'ExportFeed', 'typesWithConfigurator'], []))) {
            $configuratorItems = $exportFeed->get('configuratorItems');

            if (empty($configuratorItems) || count($configuratorItems) == 0) {
                throw new Exceptions\BadRequest($this->getInjection('language')->translate('noConfiguratorItems', 'exceptions', 'ExportFeed'));
            }
        }

        $this->prepareFeedViaLanguage();
        $this->prepareFeedViaChannel();

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
            if (isset($row['mask'])) {
                $item->set('mask', $row['mask']);
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
            $post->locale = $feed->get('language');
            $post->exportFeedId = $feed->get('id');
            $post->exportFeedName = $feed->get('name');
            $post->attributeId = $attribute->get('id');
            $post->attributeName = $attribute->get('name');

            if (!empty($feed->get('channelId'))) {
                $post->scope = 'Channel';
                $post->channelId = $feed->get('channelId');
                $post->channelName = $feed->get('channelName');
            }

            if ($attribute->get('type') === 'currency') {
                $post->mask = "{{value}} {{currency}}";
            }

            if ($attribute->get('type') === 'unit') {
                $post->mask = "{{value}} {{unit}}";
            }

            $exportConfiguratorItemService->createEntity($post);
        }

        return true;
    }

    public function removeAllItems(string $feedId): bool
    {
        $this->getRepository()->removeConfiguratorItems($feedId);

        return true;
    }

    public function readEntity($id)
    {
        $this->prepareFeedViaLanguage();
        $this->prepareFeedViaChannel();

        return parent::readEntity($id);
    }

    public function findLinkedEntities($id, $link, $params)
    {
        if ($link === 'configuratorItems') {
            $this->prepareFeedViaLanguage();
            $this->prepareFeedViaChannel();
        }

        return parent::findLinkedEntities($id, $link, $params);
    }

    public function prepareFeedViaLanguage(): void
    {
        $languages = ['mainLocale'];
        if ($this->getConfig()->get('isMultilangActive', false)) {
            $languages = array_merge($languages, $this->getConfig()->get('inputLanguageList', []));
        }
        $languages = implode("','", $languages);

        $this->getEntityManager()->getPDO()->exec("UPDATE `export_feed` SET language='mainLocale' WHERE language NOT IN ('$languages')");
        $this->getEntityManager()->getPDO()->exec("UPDATE `export_configurator_item` SET deleted=1 WHERE locale NOT IN ('$languages')");
    }

    public function prepareFeedViaChannel(): void
    {
        $this->getEntityManager()->getPDO()->exec(
            "UPDATE `export_feed` SET channel_id=null WHERE channel_id IS NOT NULL AND channel_id NOT IN (SELECT id FROM `channel` WHERE deleted=0)"
        );
        $this->getEntityManager()->getPDO()->exec(
            "UPDATE `export_configurator_item` SET deleted=1 WHERE `type`='Attribute' AND channel_id IS NOT NULL AND channel_id NOT IN (SELECT id FROM `channel` WHERE deleted=0)"
        );
    }

    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        foreach ($entity->getFeedFields() as $name => $value) {
            $entity->set($name, $value);
        }

        if ($entity->get('type') === 'simple') {
            $entity->set('convertCollectionToString', true);
            $entity->set('convertRelationsToString', true);
        }

        $entity->set('hasConfigurator', in_array($entity->get('type'), $this->getMetadata()->get('scopes.ExportFeed.typesWithConfigurator')));
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
                    'sortOrderField'            => $feed->get('sortOrderField'),
                    'sortOrderDirection'        => $feed->get('sortOrderDirection'),
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
                    'filterField'               => $item->get('filterField'),
                    'filterFieldValue'          => $item->get('filterFieldValue'),
                    'offsetRelation'            => $item->get('offsetRelation'),
                    'limitRelation'             => $item->get('limitRelation'),
                    'valueModifier'             => $item->get('valueModifier'),
                ];

                if ($feed->get('type') === 'simple') {
                    $row['convertCollectionToString'] = true;
                    $row['convertRelationsToString'] = true;
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
     * @return string
     */
    protected function pushExport(array $data): string
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
                $jobId = $this->pushExportJob($jobName, $data);
                $data['offset'] = $data['offset'] + $data['limit'];
                $i++;
            }
        } else {
            $jobId = $this->pushExportJob($data['feed']['name'], $data);
        }

        return $jobId;
    }

    protected function pushExportJob(string $jobName, array $data): string
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

        $this->getEntityManager()->saveEntity($exportJob);

        $data['exportJobId'] = $exportJob->get('id');

        $qmJobName = sprintf($this->getInjection('language')->translate('exportName', 'additionalTranslates', 'ExportFeed'), $jobName);

        $this->getInjection('queueManager')->push($qmJobName, 'QueueManagerExport', $data);

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
}
