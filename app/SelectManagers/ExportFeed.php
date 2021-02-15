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

namespace Export\SelectManagers;

use Treo\Core\SelectManagers\Base;

/**
 * ExportFeed select manager
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
            'value'     => $this->getMetadata()->get(['entityDefs', 'ExportFeed', 'fields', 'type', 'options'], [])
        ];

        if (!empty($params['exportEntity'])) {
            $params['where'][] = [
                'type'      => 'in',
                'attribute' => 'id',
                'value'     => $this->getRepository()->getIdsByExportEntity((string)$params['exportEntity'])
            ];
        }

        return parent::getSelectParams($params, $withAcl, $checkWherePermission);
    }

    protected function getRepository(): \Export\Repositories\ExportFeed
    {
        return $this->getEntityManager()->getRepository('ExportFeed');
    }
}
