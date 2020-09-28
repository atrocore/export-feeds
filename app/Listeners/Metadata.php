<?php

declare(strict_types=1);

namespace Export\Listeners;

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class ExportFeedEntity
 *
 * @author m.kokhanskyi@treolabs.com
 */
class Metadata extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function modify(Event $event): void
    {
        $this->addTypes($event);
    }

    /**
     * @param Event $event
     */
    protected function addTypes(Event $event): void
    {
        $data = $event->getArgument('data');
        $allowedTypes = $data['entityDefs']['ExportFeed']['fields']['type']['options'];

        if (!empty($data['app']['export']['type'])) {
            $types = $data['app']['export']['type'];
            foreach ($types as $type => $class) {
                if (in_array($type, $allowedTypes, true) || !$this->isAllowedType($type, $event)) {
                    continue;
                }
                if (!empty($data['entityDefs']['ExportFeed']['fields']['type']['options'])) {
                    $allowedTypes[] = $type;
                }
            }
        }

        $data['entityDefs']['ExportFeed']['fields']['type']['options'] = $allowedTypes;
        $event->setArgument('data', $data);
    }

    /**
     * @param string $type
     * @param Event $event
     * @return bool
     */
    protected function isAllowedType(string $type, Event $event): bool
    {
        $result = true;
        $methodCheck = 'isAllowedType' . ucfirst($type);
        if (method_exists($this, $methodCheck)) {
            $result = $this->{$methodCheck}($event);
        }

        return $result;
    }
    /**
     * @param Event $event
     * @return bool
     */
    protected function isAllowedTypeProductImage(Event $event): bool
    {
        return $this->getMetadata()->isModuleInstalled('Dam');
    }

    /**
     * @param Event $event
     * @return bool
     */
    protected function isAllowedTypeProductAsset(Event $event): bool
    {
        return $this->isAllowedTypeProductImage($event);
    }
}
