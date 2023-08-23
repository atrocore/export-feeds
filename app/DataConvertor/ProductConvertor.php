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

namespace Export\DataConvertor;

use Espo\Core\EventManager\Event;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;

class ProductConvertor extends Convertor
{
    protected array $rowPavs = ['hash' => null, 'pavs' => null];

    public function convert(array $record, array $configuration): array
    {
        if (isset($configuration['attributeId'])) {
            return $this->convertAttributeValue($record, $configuration);
        }

        return parent::convert($record, $configuration);
    }

    protected function convertAttributeValue(array $record, array $configuration): array
    {
        /**
         * Get from DB only for different row
         */
        $hash = md5(json_encode($record));
        if ($this->rowPavs['hash'] !== $hash) {
            $this->rowPavs['hash'] = $hash;
            $productPavs = $this->getService('Product')->findLinkedEntities($record['id'], 'productAttributeValues', []);
            $this->rowPavs['pavs'] = array_key_exists('collection', $productPavs) ? $productPavs['collection'] : new EntityCollection();
        }

        /**
         * Exit if empty
         */
        if (empty($this->rowPavs['pavs']) || count($this->rowPavs['pavs']) === 0) {
            return [];
        }

        $pavs = $this->rowPavs['pavs'];

        $result = [];

        $result[$configuration['column']] = $configuration['markForNotLinkedAttribute'];

        $productAttribute = null;

        foreach ($pavs as $pav) {
            if ($this->isLanguageEquals($pav, $configuration) && $pav->get('attributeId') == $configuration['attributeId'] && $pav->get('scope') == 'Global') {
                $productAttribute = $pav;
                break 1;
            }
        }

        if (!empty($configuration['channelId'])) {
            foreach ($pavs as $pav) {
                if (
                    $this->isLanguageEquals($pav, $configuration)
                    && $pav->get('attributeId') == $configuration['attributeId']
                    && $pav->get('scope') == 'Channel'
                    && $pav->get('channelId') == $configuration['channelId']
                ) {
                    $productAttribute = $pav;
                    break 1;
                }
            }
        }

        if (!empty($productAttribute)) {
            // exit if replaceAttributeValues disabled
            if (empty($configuration['replaceAttributeValues']) && $productAttribute->get('scope') === 'Global' && !empty($configuration['channelId'])) {
                return $result;
            }
            $result = $this->convertType($this->getTypeForAttribute($productAttribute->get('attributeId'), $configuration['attributeValue']), $productAttribute->toArray(), array_merge($configuration, ['field' => $this->getFieldForAttribute($configuration)]));
        }

        $eventPayload = [
            'result'           => $result,
            'productAttribute' => $productAttribute,
            'record'           => $record,
            'configuration'    => $configuration
        ];

        return $this->container->get('eventManager')->dispatch('ProductConvertor', 'convertAttributeValue', new Event($eventPayload))->getArgument('result');
    }

    public function getFieldForAttribute($configuration)
    {
        return $configuration['attributeValue'] ?? 'value';
    }

    protected function isLanguageEquals(Entity $pav, array $configuration): bool
    {
        if (!empty($GLOBALS['languagePrism']) || empty($pav->get('attributeIsMultilang'))) {
            return true;
        }

        return $pav->get('language') === $configuration['language'];
    }
}
