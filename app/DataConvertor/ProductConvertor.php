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

        $pavCollection = $this->getMemoryStorage()->get('pavCollection');

        /**
         * Exit if empty
         */
        if (!$pavCollection instanceof EntityCollection) {
            return $result;
        }

        $productAttribute = null;

        foreach ($pavCollection as $pav) {
            if ($pav->get('productId') === $record['id'] && $this->isLanguageEquals($pav, $configuration) && $pav->get('attributeId') == $configuration['attributeId'] && $pav->get('scope') == 'Global') {
                $productAttribute = $pav;
                break 1;
            }
        }

        if (!empty($configuration['channelId'])) {
            foreach ($pavCollection as $pav) {
                if (
                    $pav->get('productId') === $record['id']
                    && $this->isLanguageEquals($pav, $configuration)
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
            $result = $this->convertType($this->getTypeForAttribute($productAttribute->get('attributeType'), $configuration['attributeValue']), $productAttribute->toArray(), array_merge($configuration, ['field' => $this->getFieldForAttribute($configuration)]));
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

    protected function isLanguageEquals(Entity $pav, array $configuration): bool
    {
        if (!empty($GLOBALS['languagePrism']) || empty($pav->get('attributeIsMultilang'))) {
            return true;
        }

        return $pav->get('language') === $configuration['language'];
    }
}
