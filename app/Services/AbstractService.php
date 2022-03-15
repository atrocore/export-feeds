<?php
/*
 * Export Feeds
 * Free Extension
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * This software is not allowed to be used in Russia and Belarus.
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
