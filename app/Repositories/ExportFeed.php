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

namespace Export\Repositories;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Templates\Repositories\Base;
use Espo\Core\Utils\Json;
use Espo\ORM\Entity;
use Export\Entities\ExportFeed as ExportFeedEntity;

class ExportFeed extends Base
{
    public function removeInvalidConfiguratorItems(string $exportFeedId): void
    {
        $exportFeed = $this->get($exportFeedId);
        if (empty($exportFeed)) {
            return;
        }

        $languages = ['', 'main'];
        if ($this->getConfig()->get('isMultilangActive', false)) {
            $languages = array_merge($languages, $this->getConfig()->get('inputLanguageList', []));
        }
        $languages = implode("','", $languages);

        try {
            /**
             * Prepare language configuration
             */
            $this->getPDO()->exec(
                "UPDATE `export_feed` SET `language`='' WHERE id='{$exportFeed->get('id')}' AND `language` NOT IN ('$languages')"
            );
            $this->getPDO()->exec(
                "UPDATE `export_configurator_item` SET `deleted`=1 WHERE `language` NOT IN ('$languages')"
            );

            /**
             * Prepare scope|channel configuration
             */
            $this->getPDO()->exec(
                "UPDATE `export_feed` SET channel_id=null WHERE channel_id IS NOT NULL AND channel_id NOT IN (SELECT id FROM `channel` WHERE deleted=0)"
            );
            $this->getPDO()->exec(
                "UPDATE `export_configurator_item` SET deleted=1 WHERE export_feed_id='{$exportFeed->get('id')}' AND type='Attribute' AND channel_id IS NOT NULL AND channel_id NOT IN (SELECT id FROM `channel` WHERE deleted=0)"
            );
            $this->getPDO()->exec(
                "UPDATE `export_configurator_item` SET deleted=1 WHERE export_feed_id='{$exportFeed->get('id')}' AND type='Attribute' AND attribute_id NOT IN (SELECT id FROM attribute WHERE deleted=0)"
            );
        } catch (\Throwable $e) {
            $GLOBALS['log']->error('Remove invalid configurator items failed: ' . $e->getMessage());
        }
    }

    public function getIdsByExportEntity(string $exportEntity): array
    {
        $feeds = $this
            ->select(['id'])
            ->where(['data*' => '%\"entity\":\"' . $exportEntity . '\"%'])
            ->find();

        return array_column($feeds->toArray(), 'id');
    }

    public function removeConfiguratorItems(string $entityType, string $id): void
    {
        $this->getEntityManager()->getRepository('ExportConfiguratorItem')->where([lcfirst($entityType) . 'Id' => $id])->removeCollection();
    }

    protected function beforeSave(Entity $entity, array $options = [])
    {
        $fetchedEntity = $entity->getFeedField('entity');

        $this->setFeedFieldsToDataJson($entity);

        if (empty($options['skipAll'])) {
            $this->isDelimiterValid($entity);
        }

        if ($entity->isNew()) {
            $entity->set('lastStatus', null);
            $entity->set('lastTime', null);
        }

        parent::beforeSave($entity, $options);

        if (!$entity->isNew() && $entity->isAttributeChanged('language') && !empty($entity->get('language'))) {
            // Fix column type when global language is set on export Feed
            $qb = $this->getConnection()->createQueryBuilder();
            $qb->update('export_configurator_item')
                ->set('column_type', ':newColumnType')
                ->where('column_type = :columnType and export_feed_id= :exportFeedId')
                ->setParameters([
                    'newColumnType' => 'name',
                    'columnType'    => 'internal',
                    'exportFeedId'  => $entity->get('id')
                ])
                ->andWhere($qb->expr()->eq('deleted', 0))
                ->executeQuery();
        }

        if ($entity->get('type') === 'simple') {
            $entity->set('convertCollectionToString', true);
            $entity->set('convertRelationsToString', true);

            // remove configurator items on Entity change
            if (!$entity->isNew() && $entity->has('entity') && $fetchedEntity !== $entity->get('entity')) {
                $this->removeConfiguratorItems('ExportFeed', $entity->get('id'));
            }
        }
    }

    protected function beforeRemove(Entity $entity, array $options = [])
    {
        parent::beforeRemove($entity, $options);

        $this->removeConfiguratorItems('ExportFeed', $entity->get('id'));
    }

    protected function init()
    {
        parent::init();

        $this->addDependency('language');
    }

    protected function setFeedFieldsToDataJson(Entity $entity): void
    {
        $data = !empty($data = $entity->get('data')) ? Json::decode(Json::encode($data), true) : [];

        foreach ($this->getMetadata()->get(['entityDefs', 'ExportFeed', 'fields'], []) as $field => $row) {
            if (empty($row['notStorable']) || empty($row['dataField'])) {
                continue 1;
            }
            if ($entity->has($field)) {
                $data[ExportFeedEntity::DATA_FIELD][$field] = $entity->get($field);
            }
        }

        if (isset($data['configuration'])) {
            unset($data['configuration']);
        }

        $entity->set('data', Json::decode(Json::encode($data)));
    }

    protected function isDelimiterValid(Entity $entity): void
    {
        $delimiters = [
            (string)$entity->getFeedField('delimiter'),
            (string)$entity->getFeedField('decimalMark'),
            (string)$entity->getFeedField('thousandSeparator'),
            (string)$entity->getFeedField('fieldDelimiterForRelation'),
        ];

        if ($entity->get('fileType') == 'csv') {
            $delimiters[] = (string)$entity->getFeedField('csvFieldDelimiter');
        }

        if (count(array_unique($delimiters)) !== count($delimiters)) {
            throw new BadRequest($this->getInjection('language')->translate('delimitersMustBeDifferent', 'messages', 'ExportFeed'));
        }
    }
}
