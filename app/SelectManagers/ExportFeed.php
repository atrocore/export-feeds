<?php

namespace Export\SelectManagers;

use Treo\Core\SelectManagers\Base;

/**
 * ExportFeed select manager
 *
 * @author m.kokhanskyi <m.kokhanskyi@treolabs.com>
 */
class ExportFeed extends Base
{
    /**
     * @inheritdoc
     */
    public function getSelectParams(array $params, $withAcl = false, $checkWherePermission = false)
    {
        // filtering by ExportFeed types
        $params['where'][] = [
            'type'      => 'in',
            'attribute' => 'type',
            'value'     => $this->getMetadata()->get(['entityDefs','ExportFeed','fields','type','options'], [])
        ];

        return parent::getSelectParams($params, $withAcl, $checkWherePermission);
    }
}
