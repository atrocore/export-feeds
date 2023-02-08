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

    public function removeConfiguratorItems(string $exportFeedId): void
    {
        $this->getEntityManager()->getRepository('ExportConfiguratorItem')->where(['exportFeedId' => $exportFeedId])->removeCollection();
    }

    protected function beforeSave(Entity $entity, array $options = [])
    {
        $fetchedEntity = $entity->getFeedField('entity');

        $this->setFeedFieldsToDataJson($entity);

        if (empty($options['skipAll'])) {
            $this->isDelimiterValid($entity);
        }

        parent::beforeSave($entity, $options);

        if ($entity->get('type') === 'simple') {
            $entity->set('convertCollectionToString', true);
            $entity->set('convertRelationsToString', true);

            // remove configurator items on Entity change
            if (!$entity->isNew() && $entity->has('entity') && $fetchedEntity !== $entity->get('entity')) {
                $this->removeConfiguratorItems($entity->get('id'));
            }
        }
    }

    protected function beforeRemove(Entity $entity, array $options = [])
    {
        parent::beforeRemove($entity, $options);

        $this->removeConfiguratorItems($entity->get('id'));
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

        if ($entity->getFeedField('entity') === 'Product') {
            $delimiters[] = (string)$entity->getFeedField('markForNotLinkedAttribute');
        }

        if (count(array_unique($delimiters)) !== count($delimiters)) {
            throw new BadRequest($this->getInjection('language')->translate('delimitersMustBeDifferent', 'messages', 'ExportFeed'));
        }
    }
}
