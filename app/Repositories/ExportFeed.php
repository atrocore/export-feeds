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
use Espo\ORM\Entity;
use Export\DataConvertor\Base as BaseConvertor;

/**
 * ExportFeed Repository
 */
class ExportFeed extends Base
{
    public const DEFAULT_DELIMITER = ',';

    /**
     * @param Entity $entity
     * @param array  $options
     *
     * @throws BadRequest
     */
    protected function beforeSave(Entity $entity, array $options = [])
    {
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
                    'entity'        => empty($this->getMetadata()->get(['scopes', 'Product'])) ? 'User' : 'Product',
                    'allFields'     => true,
                    'delimiter'     => self::DEFAULT_DELIMITER,
                    'configuration' => []
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

    /**
     * @param Entity $entity
     *
     * @return bool
     * @throws BadRequest
     */
    protected function isDelimiterValid(Entity $entity): bool
    {
        $delimiter = (string)$entity->get('data')->delimiter;
        if (strpos($delimiter, BaseConvertor::SYSTEM_DELIMITER) !== false) {
            throw new BadRequest($this->getInjection('language')->translate('systemDelimiter', 'messages', 'ExportFeed'));
        }
        if ($entity->get('fileType') == 'csv') {
            if (strpos($entity->get('csvFieldDelimiter'), BaseConvertor::SYSTEM_DELIMITER) !== false) {
                throw new BadRequest($this->getInjection('language')->translate('systemDelimiter', 'messages', 'ExportFeed'));
            }
            foreach (str_split($delimiter) as $char) {
                if (strpos($entity->get('csvFieldDelimiter'), $char) !== false) {
                    throw new BadRequest($this->getInjection('language')->translate('delimitersMustBeDifferent', 'messages', 'ExportFeed'));
                }
            }
        }

        return true;
    }
}
