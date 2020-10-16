<?php
/*
 * This file is part of premium software, which is NOT free.
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This Software is the property of AtroCore UG (haftungsbeschränkt) and is
 * protected by copyright law - it is NOT Freeware and can be used only in one
 * project under a proprietary license, which is delivered along with this program.
 * If not, see <https://atropim.com/eula> or <https://atrodam.com/eula>.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
 */

declare(strict_types=1);

namespace Export\Listeners;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Json;
use Espo\ORM\Entity;
use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class ExportFeedEntity
 */
class ExportFeedEntity extends AbstractListener
{
    /**
     * @param Event $event
     *
     * @throws Error
     */
    public function beforeSave(Event $event)
    {
        if (!$this->isValid($event->getArgument('entity'))) {
            throw new BadRequest($this->translate(
                'Configurator settings incorrect',
                'exceptions',
                'ExportFeed'
            ));
        }
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isValid(Entity $entity): bool
    {
        $result = true;
        $configuration = Json::decode(Json::encode($entity->get('data')->configuration), true);

        foreach ($configuration as $key => $item) {
            if (isset($item['attributeId'])) {
                foreach ($configuration as $k => $i) {
                    if (isset($i['attributeId']) && $key != $k && $i['attributeId'] == $item['attributeId']
                        && $i['scope'] == $item['scope']) {
                        if ($i['scope'] == 'Global' || ($i['scope'] == 'Channel' && $i['channelId'] == $item['channelId'])) {
                            $result = false;
                        }
                    }
                }
            } elseif ($entity->get('data')->entity == 'Product' && $item['field'] == 'productCategories') {
                foreach ($configuration as $k => $i) {
                    if ($i['field'] == $item['field'] && $key != $k && $i['scope'] == $item['scope']) {
                        if ($i['scope'] == 'Global' || ($i['scope'] == 'Channel' && $i['channelId'] == $item['channelId'])) {
                            $result = false;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Translate
     *
     * @param string $key
     *
     * @param string $label
     * @param string $scope
     *
     * @return string
     */
    protected function translate(string $key, string $label, $scope = ''): string
    {
        return $this->getContainer()->get('language')->translate($key, $label, $scope);
    }
}
