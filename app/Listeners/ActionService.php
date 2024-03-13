<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore GmbH (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

declare(strict_types=1);

namespace Export\Listeners;

use Atro\Core\EventManager\Event;
use Atro\Listeners\AbstractListener;
use Espo\ORM\Entity;

class ActionService extends AbstractListener
{
    public function prepareEntityForOutput(Event $event): void
    {
        /** @var Entity $entity */
        $entity = $event->getArgument('entity');

        if (!empty($entity->get('exportFeedId'))) {
            $exportFeed = $this->getEntityManager()->getRepository('ExportFeed')->get($entity->get('exportFeedId'));
            if (!empty($exportFeed)) {
                $entity->set('exportFeedName', $exportFeed->get('name'));
            }
        }
    }
}
