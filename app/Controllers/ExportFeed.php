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
 */

declare(strict_types=1);

namespace Export\Controllers;

use Espo\Core\Templates\Controllers\Base;
use Espo\Core\Exceptions;
use Slim\Http\Request;

/**
 * ExportFeed controller
 */
class ExportFeed extends Base
{
    public function actionAddMissingFields($params, $data, Request $request): bool
    {
        if (!$request->isPost() || !property_exists($data, 'entityType') || !property_exists($data, 'id')) {
            throw new Exceptions\BadRequest();
        }

        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Exceptions\Forbidden();
        }

        return $this->getRecordService()->addMissingFields((string)$data->entityType, (string)$data->id);
    }

    public function actionAddAttributes($params, $data, Request $request): bool
    {
        if (!$request->isPost() || !property_exists($data, 'entityType') || !property_exists($data, 'id')) {
            throw new Exceptions\BadRequest();
        }

        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Exceptions\Forbidden();
        }

        return $this->getRecordService()->addAttributes($data);
    }

    public function actionRemoveAllItems($params, $data, Request $request): bool
    {
        if (!$request->isPost() || !property_exists($data, 'entityType') || !property_exists($data, 'id')) {
            throw new Exceptions\BadRequest();
        }

        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Exceptions\Forbidden();
        }

        return $this->getRecordService()->removeAllItems((string)$data->entityType, (string)$data->id);
    }

    public function actionExportFile($params, $data, Request $request): bool
    {
        if (!$request->isPost() || !property_exists($data, 'id')) {
            throw new Exceptions\BadRequest();
        }

        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Exceptions\Forbidden();
        }

        return $this->getRecordService()->exportFile($data);
    }

    public function actionExportChannel($params, $data, Request $request): bool
    {
        if (!$request->isPost() || !property_exists($data, 'id')) {
            throw new Exceptions\BadRequest();
        }

        if (!$this->getAcl()->check($this->name, 'read') || !$this->getAcl()->check('Channel', 'read')) {
            throw new Exceptions\Forbidden();
        }

        return $this->getRecordService()->exportChannel($data->id);
    }

    /**
     * @inheritDoc
     */
    protected function fetchListParamsFromRequest(&$params, $request, $data)
    {
        parent::fetchListParamsFromRequest($params, $request, $data);

        if ($request->get('exportEntity')) {
            $params['exportEntity'] = $request->get('exportEntity');
        }
    }

    public function actionEasyCatalog($params, $data, Request $request)
    {
        if (!$request->isGet() || empty($request->get("code"))) {
            throw new Exceptions\BadRequest();
        }

        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Exceptions\Forbidden();
        }

        return $this->getRecordService()->getEasyCatalog($request->get("code"), $request->get('offset'));
    }
}
