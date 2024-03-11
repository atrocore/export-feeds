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

namespace Export\Services;

use Espo\Core\Exceptions;
use Atro\Core\Templates\Services\Base;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Util;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\Core\EventManager\Event;
use Export\TemplateLoaders\AbstractTemplate;

class ExportFeed extends Base
{
    public function runExport(string $feedId, string $payload = null, ?string $priority = null): bool
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
            $data['executeNow'] = !empty($payload['executeNow']);
        }

        if (!empty($priority)) {
            $data['feed']['priority'] = $priority;
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

        switch ($exportFeed->get('fileType')) {
            case 'csv':
                $configuratorItems = $exportFeed->get('configuratorItems');
                if (empty($configuratorItems[0])) {
                    throw new Exceptions\BadRequest($this->getInjection('language')->translate('noConfiguratorItems', 'exceptions', 'ExportFeed'));
                }
                break;
            case 'xlsx':
                if (!empty($exportFeed->get('hasMultipleSheets'))) {
                    if (!empty($sheets = $exportFeed->get('sheets'))) {
                        foreach ($sheets as $sheet) {
                            if (!empty($sheet->get('isActive'))) {
                                break 2;
                            }
                        }
                    }
                    throw new Exceptions\BadRequest($this->getInjection('language')->translate('noSheets', 'exceptions', 'ExportFeed'));
                } else {
                    $configuratorItems = $exportFeed->get('configuratorItems');
                    if (empty($configuratorItems[0])) {
                        throw new Exceptions\BadRequest($this->getInjection('language')->translate('noConfiguratorItems', 'exceptions', 'ExportFeed'));
                    }
                }
                break;
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

        /** @var \Export\Services\ExportConfiguratorItem $eciService */
        $eciService = $this->getInjection('serviceFactory')->create('ExportConfiguratorItem');
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
            $item->set('column', $eciService->prepareColumnName($item));

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

            $post->attributeValue = 'value';

            switch ($attribute->get('type')) {
                case 'currency':
                    $post->mask = "{{value}} {{currency}}";
                    break;
                case 'float':
                case 'int':
                    if (!$attribute->get('measureId')) {
                        $post->attributeValue = 'valueNumeric';
                    }
                    break;
                case 'varchar':
                    if (!$attribute->get('measureId')) {
                        $post->attributeValue = 'valueString';
                    }
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
        if ($link === 'configuratorItems' && !empty($exportFeed = $this->getEntity($id))) {
            $this->getRepository()->removeInvalidConfiguratorItems($exportFeed->get('id'));
            if (!empty($exportFeed->get('language'))) {
                $params['where'][] = [
                    'type'      => 'equals',
                    'attribute' => 'language',
                    'value'     => 'main'
                ];
            }
        }

        return parent::findLinkedEntities($id, $link, $params);
    }

    public function prepareCollectionForOutput(EntityCollection $collection, array $selectParams = []): void
    {
        parent::prepareCollectionForOutput($collection, $selectParams);

        if (count($collection) > 0) {
            $connection = $this->getRepository()->getConnection();
            $qb = $connection->createQueryBuilder();

            $latestJobs = $qb
                ->select('export_feed_id, MAX(start) AS start, state')
                ->from($connection->quoteIdentifier('export_job'))
                ->where('export_feed_id IN (:ids)')
                ->setParameter('ids', array_column($collection->toArray(), 'id'), \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
                ->groupBy('export_feed_id')
                ->addGroupBy('state')
                ->orderBy('start', 'DESC')
                ->fetchAllAssociative();

            if (count($latestJobs) > 0) {
                foreach ($collection as $item) {
                    foreach ($latestJobs as $job) {
                        if ($item->id == $job['export_feed_id']) {
                            $item->set('lastStatus', $job['state']);
                            $item->set('lastTime', $job['start']);

                            continue 2;
                        }
                    }
                }
            }
        }
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

        if (empty($entity->get('lastStatus')) || empty($entity->get('lastTime'))) {
            $latestJob = $this->getEntityManager()
                ->getRepository('ExportJob')
                ->select(['state', 'start'])
                ->where([
                    'exportFeedId' => $entity->id
                ])
                ->order('start', 'DESC')
                ->findOne();

            if (!empty($latestJob)) {
                $entity->set('lastStatus', $latestJob->get('state'));
                $entity->set('lastTime', $latestJob->get('start'));
            }
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

        $this->addDependency('container');
        $this->addDependency('queueManager');
        $this->addDependency('serviceFactory');
        $this->addDependency('language');
        $this->addDependency('user');
        $this->addDependency('moduleManager');
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

        if ($sheet->getEntityType() === 'ExportFeed') {
            $feed = $sheet;
            $entityName = $sheet->getFeedField('entity');
        } else {
            $feed = $sheet->get('exportFeed');
            $entityName = $sheet->get('entity');
        }

        $configuration = [];

        /** @var \Export\Services\ExportConfiguratorItem $eciService */
        $eciService = $this->getInjection('serviceFactory')->create('ExportConfiguratorItem');

        foreach ($items['collection'] as $item) {
            $row = [
                'id'                        => $item->get('id'),
                'columnType'                => $item->get('columnType'),
                'language'                  => $item->get('language'),
                'fallbackLanguage'          => $item->get('fallbackLanguage'),
                'column'                    => $eciService->prepareColumnName($item),
                'template'                  => $feed->get('template'),
                'emptyValue'                => $feed->getFeedField('emptyValue'),
                'nullValue'                 => $feed->getFeedField('nullValue'),
                'markForNoRelation'         => $feed->getFeedField('markForNoRelation'),
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
                'type'                      => $item->get('type'),
                'fixedValue'                => $item->get('fixedValue'),
                'zip'                       => !empty($item->get('zip')),
                'fileNameTemplate'          => $item->get('fileNameTemplate'),
                'attributeValue'            => $item->get('attributeValue'),
                'entity'                    => $entityName,
                'sortOrderField'            => $sheet->get('sortOrderField'),
                'sortOrderDirection'        => $sheet->get('sortOrderDirection'),
                'script'                    => $item->get('script') ?? null,
            ];
            if ($feed->get('type') === 'simple') {
                $row['convertCollectionToString'] = true;
                $row['convertRelationsToString'] = true;
            }

            if ($item->get('type') === 'Field') {
                if ($item->get('name') !== 'id' && empty($this->getMetadata()->get(['entityDefs', $row['entity'], 'fields', $item->get('name')]))) {
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

                $row['channelLocales'] = [];
                $row['channelId'] = $item->get('channelId');

                if (!empty($channel = $item->get('channel'))) {
                    $row['channelLocales'] = $channel->get('locales');
                }

                if (empty($row['attributeValue'])) {
                    switch ($attribute->get('type')) {
                        case 'rangeInt':
                        case 'rangeFloat':
                            $row['attributeValue'] = "valueFrom";
                            break;
                        default:
                            $row['attributeValue'] = 'value';
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
        $name = $this->getInjection('language')->translate('createExportJobs', 'additionalTranslates', 'ExportFeed');
        $name = sprintf($name, $data['feed']['name']);

        $priority = empty($data['feed']['priority']) ? 'Normal' : (string)$data['feed']['priority'];

        if (!empty($data['executeNow'])) {
            $this->getServiceFactory()->create('ExportJobCreator')->run($data);
        } else {
            $this->getInjection('queueManager')->push($name, 'ExportJobCreator', $data, $priority);
        }

        return true;
    }

    /**
     * @param string $templateName
     *
     * @return string|null
     */
    public function getOriginTemplate(string $template): ?string
    {
        if (!empty($className = $this->getMetadata()->get(['app', 'templateLoaders', $template]))) {
            if (is_a($className, AbstractTemplate::class, true)) {
                $templateClass = $this->getInjection('container')->get($className);

                return $templateClass->loadTemplateFromFile();
            }
        }

        return null;
    }

    public function getAvailableTemplates(array $data): array
    {
        $result = [];

        foreach ($this->getMetadata()->get(['app', 'templateLoaders'], []) as $name => $className) {
            if (is_a($className, AbstractTemplate::class, true)) {
                $templateClass = $this->getInjection('container')->get($className);

                if ($templateClass->isTemplateCompatible($data)) {
                    $result[$name] = $templateClass->getName();
                }
            }
        }

        return $result;
    }

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

    public function duplicateSheets(Entity $entity, Entity $duplicatingEntity): void
    {
        if (empty($items = $duplicatingEntity->get('sheets')) || count($items) === 0) {
            return;
        }

        foreach ($items as $item) {
            $data = $item->toArray();
            $data['_duplicatingEntityId'] = $item->get('id');
            $data['exportFeedId'] = $entity->get('id');

            unset($data['id']);
            unset($data['createdAt']);
            unset($data['modifiedAt']);
            unset($data['createdById']);
            unset($data['modifiedById']);

            $this->getServiceFactory()->create('Sheet')->createEntity((object)$data);
        }
    }


    public function verifyCodeEasyCatalog(string $code)
    {
        $exportFeed = $this->getRepository()->where(['code' => $code])->findOne();
        if (empty($exportFeed)) {
            return 'Export Feed code is invalid';
        }

        $hasIdColumn = false;
        foreach ($exportFeed->configuratorItems as $configuratorItem) {
            if ($configuratorItem->get('column') == 'ID') {
                $hasIdColumn = true;
                break;
            }
        }

        if (!$hasIdColumn) {
            return 'This export feed has no ID column';
        }

        return 'Export feed is correctly configured';
    }

    public function getEasyCatalog($exportFeedCode, $offset)
    {
        $exportFeed = $this->getRepository()->where(['code' => $exportFeedCode])->findOne();
        if (empty($exportFeed)) {
            throw new Exceptions\NotFound();
        }
        $data = [
            'id'   => Util::generateId(),
            'feed' => $this->prepareFeedData($exportFeed)
        ];

        $data['offset'] = !empty($offset) ? (int)$offset : 0;
        $data['limit'] = empty($data['feed']['limit']) ? \PHP_INT_MAX : $data['feed']['limit'];

        $exportService = $this->getExportTypeService($data['feed']['type']);

        return [
            "total"      => $exportService->getCount($data),
            "urlColumns" => $exportService->getUrlColumns(),
            "records"    => $exportService->exportEasyCatalogJson(),
        ];
    }
}
