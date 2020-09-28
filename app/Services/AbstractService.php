<?php

declare(strict_types=1);

namespace Export\Services;

use Export\Entities\ExportFeed;

/**
 * Class AbstractService
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
abstract class AbstractService extends \Treo\Services\AbstractService
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
