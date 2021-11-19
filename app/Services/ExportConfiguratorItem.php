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

use Espo\Core\Templates\Services\Base;
use Espo\Core\Utils\Language;
use Espo\ORM\Entity;

class ExportConfiguratorItem extends Base
{
    protected $mandatorySelectAttributeList = ['exportFeedId', 'entity', 'type', 'columnType'];

    protected array $languages = [];

    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        if (empty($feed = $entity->get('exportFeed'))) {
            return;
        }

        $entity->set('entity', $feed->getFeedField('entity'));
        $entity->set('column', $this->prepareColumnName($entity));
    }

    protected function prepareColumnName(Entity $entity): string
    {
        $column = (string)$entity->get('column');

        if (empty($entity->get('columnType')) || $entity->get('columnType') === 'name') {
            $fieldData = $this->getMetadata()->get(['entityDefs', $entity->get('entity'), 'fields', $entity->get('name')]);
            if (!empty($fieldData['multilangLocale'])) {
                $column = $this->getLanguage($fieldData['multilangLocale'])->translate($fieldData['multilangField'], 'fields', $entity->get('entity'));
            } else {
                $column = $this->getInjection('language')->translate($entity->get('name'), 'fields', $entity->get('entity'));
            }
        } elseif ($entity->get('columnType') === 'internal') {
            $column = $this->getInjection('language')->translate($entity->get('name'), 'fields', $entity->get('entity'));
        }

        return $column;
    }

    protected function init()
    {
        parent::init();

        $this->addDependency('language');
        $this->addDependency('container');
    }

    protected function getLanguage(string $locale): Language
    {
        if (!isset($this->languages[$locale])) {
            $this->languages[$locale] = new Language($this->getInjection('container'), $locale);
        }

        return $this->languages[$locale];
    }
}