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

namespace Export\Listeners;

use Atro\Core\EventManager\Event;
use Atro\Listeners\AbstractListener;

class Metadata extends AbstractListener
{
    public function modify(Event $event): void
    {
        $data = $event->getArgument('data');

        if (!empty($data['scopes']['Channel']['entity'])) {
            $data['entityDefs']['ExportFeed']['fields']['channel']['type'] = 'link';
            $data['entityDefs']['ExportFeed']['fields']['channel']['tooltip'] = true;
            $data['entityDefs']['ExportFeed']['links']['channel']['type'] = 'belongsTo';
            $data['entityDefs']['ExportFeed']['links']['channel']['entity'] = 'Channel';
        }

        if (!empty($data['clientDefs']['ExportFeed']['relationshipPanels']['configuratorItems'])) {
            $data['clientDefs']['ExportFeed']['relationshipPanels']['configuratorItems']['dragDrop']['maxSize'] = $this->getConfig()->get('recordsPerPageSmall', 20);
        }

        $data['entityDefs']['ExportFeed']['fields']['lastStatus'] = [
            'type' => 'enum',
            'notStorable' => true,
            'filterDisabled' => true,
            'readOnly' => true,
            'options' => $data['entityDefs']['ExportJob']['fields']['state']['options'],
            'optionColors' => $data['entityDefs']['ExportJob']['fields']['state']['optionColors']
        ];

        $event->setArgument('data', $data);
    }
}
