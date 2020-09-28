<?php

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
 *
 * @author r.zablodskiy@treolabs.com
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
