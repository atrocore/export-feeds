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

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class ExportFeedEntity
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
