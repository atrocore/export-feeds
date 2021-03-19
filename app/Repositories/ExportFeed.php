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
        if ($entity->get('type') == 'simple') {
            if (!empty($entity->get('data'))) {
                if (!$this->isDelimiterValid($entity)) {
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
        if (strpos($delimiter, '|') !== false) {
            throw new BadRequest($this->getInjection('language')->translate('systemDelimiter', 'messages', 'ExportFeed'));
        }
        if ($entity->get('fileType') == 'csv') {
            if (strpos($entity->get('csvFieldDelimiter'), '|') !== false) {
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
