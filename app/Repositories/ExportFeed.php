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

/**
 * ExportFeed Repository
 */
class ExportFeed extends Base
{
    /**
     * @param Entity $entity
     * @param array  $options
     *
     * @throws BadRequest
     */
    protected function beforeSave(Entity $entity, array $options = [])
    {
        $this->setFeedFieldsToDataJson($entity);

        if ($entity->get('type') == 'simple') {
            if ($entity->isNew()) {
                if (empty($entity->get('fileType'))) {
                    $types = $this->getMetadata()->get(['app', 'export', 'fileTypes', $entity->get('type')], []);
                    $first = array_shift($types);
                    if (!empty($first)) {
                        $entity->set('fileType', $first);
                    }
                }

                $data = [
                    'entity'                    => empty($this->getMetadata()->get(['scopes', 'Product'])) ? 'User' : 'Product',
                    'allFields'                 => true,
                    'delimiter'                 => '_',
                    'decimalMark'               => ',',
                    'thousandSeparator'         => '',
                    'markForNotLinkedAttribute' => '--',
                    'fieldDelimiterForRelation' => \Export\DataConvertor\Base::DELIMITER,
                    'configuration'             => []
                ];

                $entity->set('data', $data);

            } else {
                if (empty($entity->get('data')) || empty($entity->get('data')->configuration) || !$this->isDelimiterValid($entity)) {
                    throw new BadRequest($this->getInjection('language')->translate('configuratorSettingsIncorrect', 'exceptions', 'ExportFeed'));
                }
            }
        }

        parent::beforeSave($entity, $options);
    }

    /**
     * @param string $exportEntity
     *
     * @return array
     */
    public function getIdsByExportEntity(string $exportEntity): array
    {
        return $this
            ->getEntityManager()
            ->nativeQuery('SELECT id FROM `export_feed` WHERE deleted=0 AND `export_feed`.data LIKE "%\"entity\":\"' . $exportEntity . '\"%"')
            ->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @param Entity $entity
     *
     * @return bool
     * @throws BadRequest
     */
    protected function isDelimiterValid(Entity $entity): bool
    {
        $data = $entity->get('data');

        $requiredMessage = $this->getInjection('language')->translate('fieldIsRequired', 'messages');
        if (empty($data->delimiter)) {
            throw new BadRequest(str_replace('{field}', $this->getInjection('language')->translate('delimiter', 'fields', 'ExportFeed'), $requiredMessage));
        }

        if (empty($data->decimalMark)) {
            throw new BadRequest(str_replace('{field}', $this->getInjection('language')->translate('decimalMark', 'fields', 'ExportFeed'), $requiredMessage));
        }

        if (empty($data->fieldDelimiterForRelation)) {
            throw new BadRequest(str_replace('{field}', $this->getInjection('language')->translate('fieldDelimiterForRelation', 'fields', 'ExportFeed'), $requiredMessage));
        }

        $delimiters = [
            (string)$data->delimiter,
            (string)$data->decimalMark,
            (string)$data->thousandSeparator,
            (string)$data->fieldDelimiterForRelation,
        ];

        if ($entity->getFeedField('fileType') == 'csv') {
            $delimiters[] = $entity->getFeedField('csvFieldDelimiter');
        }

        if ($entity->get('data')->entity === 'Product') {
            $delimiters[] = (string)$data->markForNotLinkedAttribute;
        }

        if (count(array_unique($delimiters)) !== count($delimiters)) {
            throw new BadRequest($this->getInjection('language')->translate('delimitersMustBeDifferent', 'messages', 'ExportFeed'));
        }

        return true;
    }
}
