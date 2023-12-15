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

namespace Export\DataConvertor;

use Espo\Core\EventManager\Event;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;

class ProductConvertor extends Convertor
{
    public function convert(array $record, array $configuration): array
    {
        if (isset($configuration['attributeId'])) {
            return $this->convertAttributeValue($record, $configuration);
        }

        return parent::convert($record, $configuration);
    }

    protected function convertAttributeValue(array $record, array $configuration): array
    {
        $result = [];

        $result[$configuration['column']] = $configuration['markForNoRelation'];

        $pavCollectionKeys = $this->getMemoryStorage()->get('pavCollectionKeys');

        /**
         * Exit if empty
         */
        if (empty($pavCollectionKeys)) {
            return $result;
        }

        $language = $configuration['language'];
        if (!empty($GLOBALS['languagePrism'])) {
            $language = $GLOBALS['languagePrism'];
        }

        $productAttribute = null;

        // find Global
        $key = implode('_', [$record['id'], $configuration['attributeId'], $language, 'Global', '']);
        if (isset($pavCollectionKeys[$key])) {
            $productAttribute = $this->getMemoryStorage()->get($pavCollectionKeys[$key]);
        }

        // find Channel
        if (!empty($configuration['channelId'])) {
            $key = implode('_', [$record['id'], $configuration['attributeId'], $language, 'Channel', $configuration['channelId']]);
            if (isset($pavCollectionKeys[$key])) {
                $productAttribute = $this->getMemoryStorage()->get($pavCollectionKeys[$key]);
            }
        }

        if (!empty($productAttribute)) {
            // exit if replaceAttributeValues disabled
            if (empty($configuration['replaceAttributeValues']) && $productAttribute->get('scope') === 'Global' && !empty($configuration['channelId'])) {
                return $result;
            }
            $type = $this->getTypeForAttribute($productAttribute->get('attributeType'), $configuration['attributeValue']);
            $result = $this->convertType($type, $productAttribute->toArray(), array_merge($configuration, ['field' => $this->getFieldForAttribute($configuration)]));
        }

        $eventPayload = [
            'result'           => $result,
            'productAttribute' => $productAttribute,
            'record'           => $record,
            'configuration'    => $configuration
        ];

        return $this->getEventManager()->dispatch('ProductConvertor', 'convertAttributeValue', new Event($eventPayload))->getArgument('result');
    }

    public function getFieldForAttribute($configuration)
    {
        if (in_array($configuration['attributeValue'], ['valueNumeric', 'valueString'])) {
            return 'value';
        }
        return $configuration['attributeValue'] ?? 'value';
    }
}
