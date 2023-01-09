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

use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;

class ProductConvertor extends Convertor
{
    protected array $rowPavs = ['hash' => null, 'pavs' => null];

    public function convert(array $record, array $configuration, bool $toString = false): array
    {
        if (isset($configuration['attributeId'])) {
            return $this->convertAttributeValue($record, $configuration, $toString);
        }

        return parent::convert($record, $configuration, $toString);
    }

    protected function convertAttributeValue(array $record, array $configuration, bool $toString = false): array
    {
        /**
         * Get from DB only for different row
         */
        $hash = md5(json_encode($record));
        if ($this->rowPavs['hash'] !== $hash) {
            $this->rowPavs['hash'] = $hash;
            $productPavs = $this->getService('ProductAttributeValue')->findEntities([
                'where' => [
                    [
                        'type'      => 'equals',
                        'attribute' => 'productId',
                        'value'     => $record['id'],
                    ]
                ]
            ]);
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

        if ($toString) {
            $result[$configuration['column']] = $configuration['markForNotLinkedAttribute'];
        }

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
            $result = $this->convertType($productAttribute->get('attributeType'), $productAttribute->toArray(), array_merge($configuration, ['field' => 'value']), $toString);
        }

        return $result;
    }

    protected function isLanguageEquals(Entity $pav, array $configuration): bool
    {
        if (empty($pav->get('isAttributeMultiLang'))) {
            return true;
        }

        return $pav->get('language') === $configuration['locale'];
    }
}
