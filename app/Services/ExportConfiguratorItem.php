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
use Espo\Core\Utils\Util;
use Espo\ORM\Entity;

class ExportConfiguratorItem extends Base
{
    protected $mandatorySelectAttributeList
        = [
            'exportFeedId',
            'sheetId',
            'entity',
            'type',
            'columnType',
            'exportBy',
            'exportIntoSeparateColumns',
            'sortOrder',
            'attributeId',
            'language',
            'scope',
            'channelId',
            'channelName',
            'fixedValue',
            'zip',
            'attributeValue',
            'virtualFields'
        ];

    protected array $languages = [];

    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        if (empty($feed = $entity->get('exportFeed')) && empty($sheet = $entity->get('sheet'))) {
            return;
        }

        if (!empty($sheet)) {
            $entity->set('entity', $sheet->get('entity'));
            $feed = $sheet->get('exportFeed');
        } else {
            $entity->set('entity', $feed->getFeedField('entity'));
        }

        $entity->set('column', $this->prepareColumnName($entity));
        $entity->set('exportFeedLanguage', !empty($feed->get('language')) ? $feed->get('language') : null);
        $entity->set('isAttributeMultiLang', false);
        $entity->set('attributeNameValue', $entity->get('name'));
        $entity->set('editable', $this->getAcl()->check($feed, 'edit'));

        $entity->set('fileNameTemplate', $entity->getVirtualField('fileNameTemplate'));

        if ($entity->get('type') === 'Attribute' && !empty($entity->get('attributeId'))) {
            $attribute = $this->getEntityManager()->getRepository('Attribute')->get($entity->get('attributeId'));
            if (!empty($attribute)) {
                $entity->set('attributeNameValue', $attribute->get('name'));
                $entity->set('isAttributeMultiLang', !empty($attribute->get('isMultilang')));
                $entity->set('attributeType', $attribute->get('type'));
                $entity->set('attributeCode', $attribute->get('code'));
            }
        }
    }

    public function updateEntity($id, $data)
    {
        if (property_exists($data, '_previousItemId') && property_exists($data, '_itemId')) {
            $data->previousItem = $data->_previousItemId;
            unset($data->_previousItemId);
            unset($data->_itemId);
        }

        return parent::updateEntity($id, $data);
    }

    protected function isEntityUpdated(Entity $entity, \stdClass $data): bool
    {
        if (property_exists($data, 'sortOrder')) {
            return true;
        }

        return parent::isEntityUpdated($entity, $data);
    }

    public function prepareColumnName(Entity $entity): string
    {
        if ($entity->get('type') === 'Attribute') {
            return $this->prepareAttributeColumnName($entity);
        }

        return $this->prepareFieldColumnName($entity);
    }

    protected function prepareFieldColumnName(Entity $entity): string
    {
        $columnType = $entity->get('columnType') ?? 'name';

        $language = !empty($entity->get('language')) && $entity->get('language') !== 'main' ? $entity->get('language') : 'main';
        $mainLanguage = $this->getConfig()->get('mainLanguage');

        switch ($columnType) {
            case 'name':
                if ($language === 'main') {
                    $lang = $mainLanguage;
                    if (!empty($exportFeed = $entity->get('exportFeed')) && !empty($exportFeed->get('language')) && $exportFeed->get('language') !== 'main') {
                        $lang = $exportFeed->get('language');
                    }
                    $column = $this->getLanguage($lang)->translate($entity->get('name'), 'fields', $entity->get('entity'));
                } else {
                    $column = $this->getLanguage($language)->translate($entity->get('name'), 'fields', $entity->get('entity'));
                }
                break;
            case 'internal':
                $column = $this->getLanguage($mainLanguage)->translate($entity->get('name'), 'fields', $entity->get('entity'));
                $language = !empty($entity->get('language')) && $entity->get('language') !== 'main' ? $entity->get('language') : '';
                if (!empty($language)) {
                    $column .= ' / ' . $language;
                }
                break;
            case 'custom':
                $column = (string)$entity->get('column');
                break;
            default:
                $column = '-';
        }

        return $column;
    }

    protected function prepareAttributeColumnName(Entity $entity): string
    {
        if (empty($attribute = $entity->get('attribute'))) {
            return '-';
        }

        $columnType = $entity->get('columnType') ?? 'name';

        $language = $entity->get('language');

        if ($language === 'main') {
            $language = '';
            if (!empty($exportFeed = $entity->get('exportFeed')) && !empty($exportFeed->get('language')) && $exportFeed->get('language') !== 'main') {
                $language = $exportFeed->get('language');
            }
        }

        $column = (string)$entity->get('column');

        if ($columnType === 'name') {
            $column = $attribute->get('name' . ucfirst(Util::toCamelCase(strtolower($language))));
        } elseif ($columnType === 'internal') {
            $column = $attribute->get('name');
            if (!empty($language)) {
                $column .= ' / ' . $language;
            }
        }

        return (string)$column;
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

    protected function getFieldsThatConflict(Entity $entity, \stdClass $data): array
    {
        return [];
    }
}
