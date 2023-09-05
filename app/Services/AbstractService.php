<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.md, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

declare(strict_types=1);

namespace Export\Services;

use Export\Entities\ExportFeed;

/**
 * Class AbstractService
 */
abstract class AbstractService extends \Espo\Core\Templates\Services\HasContainer
{

    /**
     * Get ExportFeed
     *
     * @param string $id
     *
     * @return ExportFeed|null
     */
    protected function getExportFeed(string $id): ?ExportFeed
    {
        // prepare name
        $name = 'ExportFeed';

        // prepare select params
        $selectParams = $this
            ->getContainer()
            ->get('selectManagerFactory')
            ->create($name)
            ->getSelectParams([], true);

        return $this
            ->getEntityManager()
            ->getRepository($name)
            ->where(['id' => $id])
            ->findOne($selectParams);
    }
}
