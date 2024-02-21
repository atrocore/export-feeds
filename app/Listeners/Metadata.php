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
use Atro\Core\KeyValueStorages\StorageInterface;
use Atro\Listeners\AbstractListener;

class Metadata extends AbstractListener
{
    public function modify(Event $event): void
    {
        $data = $event->getArgument('data');

        if (isset($data['entityDefs']['Attribute'])) {
            $data['entityDefs']['ExportConfiguratorItem']['fields']['attribute'] = [
                'type' => 'link'
            ];
            $data['entityDefs']['ExportConfiguratorItem']['links']['attribute'] = [
                'type'   => 'belongsTo',
                'entity' => 'Attribute'
            ];
        }

        if (isset($data['entityDefs']['Channel'])) {
            $data['entityDefs']['ExportConfiguratorItem']['fields']['channel'] = [
                'type'    => 'link',
                'tooltip' => true
            ];
            $data['entityDefs']['ExportConfiguratorItem']['links']['channel'] = [
                'type'   => 'belongsTo',
                'entity' => 'Channel'
            ];
        }

        if (!empty($data['clientDefs']['ExportFeed']['relationshipPanels']['configuratorItems'])) {
            $data['clientDefs']['ExportFeed']['relationshipPanels']['configuratorItems']['dragDrop']['maxSize'] = $this->getConfig()->get('recordsPerPageSmall', 20);
        }

        $data['entityDefs']['ExportFeed']['fields']['lastStatus'] = [
            'type'           => 'enum',
            'notStorable'    => true,
            'filterDisabled' => true,
            'readOnly'       => true,
            'options'        => $data['entityDefs']['ExportJob']['fields']['state']['options'],
            'optionColors'   => $data['entityDefs']['ExportJob']['fields']['state']['optionColors']
        ];

        foreach ($this->getMemoryStorage()->get('dynamic_action') ?? [] as $action) {
            if ($action['type'] === 'export') {
                if ($action['usage'] === 'record' && !empty($action['source_entity'])) {
                    $data['clientDefs'][$action['source_entity']]['dynamicRecordActions'][] = [
                        'id'         => $action['id'],
                        'name'       => $action['name'],
                        'display'    => $action['display'],
                        'massAction' => !empty($action['mass_action']),
                        'acl'        => [
                            'scope'  => 'ExportFeed',
                            'action' => 'read',
                        ]
                    ];
                }
                if ($action['usage'] === 'entity' && !empty($action['source_entity'])) {
                    $data['clientDefs'][$action['source_entity']]['dynamicEntityActions'][] = [
                        'id'      => $action['id'],
                        'name'    => $action['name'],
                        'display' => $action['display'],
                        'acl'     => [
                            'scope'  => 'ExportFeed',
                            'action' => 'read',
                        ]
                    ];
                }
            }
        }

        $data['clientDefs']['Action']['dynamicLogic']['fields']['sourceEntity']['visible']['conditionGroup'][0]['type'] = 'in';
        $data['clientDefs']['Action']['dynamicLogic']['fields']['sourceEntity']['visible']['conditionGroup'][0]['attribute'] = 'type';
        $data['clientDefs']['Action']['dynamicLogic']['fields']['sourceEntity']['visible']['conditionGroup'][0]['value'][] = 'export';

        $data['clientDefs']['Action']['dynamicLogic']['fields']['payload']['visible']['conditionGroup'][0]['type'] = 'in';
        $data['clientDefs']['Action']['dynamicLogic']['fields']['payload']['visible']['conditionGroup'][0]['attribute'] = 'type';
        $data['clientDefs']['Action']['dynamicLogic']['fields']['payload']['visible']['conditionGroup'][0]['value'][] = 'export';

        $data['clientDefs']['Action']['dynamicLogic']['fields']['inBackground']['visible']['conditionGroup'][0]['value'][] = 'export';

        if (empty($data['clientDefs']['ScheduledJob']['dynamicLogic']['fields']['maximumDaysForJobExist']['visible'])) {
            $data['clientDefs']['ScheduledJob']['dynamicLogic']['fields']['maximumDaysForJobExist']['visible']['conditionGroup'][0] = [
                'type' => 'in',
                'attribute' => 'job',
                'value' => ['ExportJobRemove']
            ];
        } else {
            $data['clientDefs']['ScheduledJob']['dynamicLogic']['fields']['maximumDaysForJobExist']['visible']['conditionGroup'][0]['value'][] = 'ExportJobRemove';
        }

        $event->setArgument('data', $data);
    }

    protected function getMemoryStorage(): StorageInterface
    {
        return $this->getContainer()->get('memoryStorage');
    }
}
