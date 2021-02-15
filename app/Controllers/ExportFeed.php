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
    /**
     * @ApiDescription(description="Export data to file")
     * @ApiMethod(type="POST")
     * @ApiRoute(name="/ExportFeed/action/exportFile")
     * @ApiBody(sample="{
     *     'id': '1'
     * }")
     * @ApiResponseCode(sample="[200,401,403,404,500]")
     * @ApiParams(name="exportFeedId", type="string", is_required=1, description="ExportFeed ID")
     * @ApiReturn(sample="bool")
     *
     * @param array     $params
     * @param \stdClass $data
     * @param Request   $request
     *
     * @return bool
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     */
    public function actionExportFile($params, $data, Request $request): bool
    {
        // checking request
        if (!$request->isPost() || empty($data->id)) {
            throw new Exceptions\BadRequest();
        }

        // checking rules
        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Exceptions\Forbidden();
        }

        return $this->getRecordService()->exportFile($data);
    }

    /**
     * @ApiDescription(description="Export channel data to file")
     * @ApiMethod(type="POST")
     * @ApiRoute(name="/ExportFeed/action/exportChannel")
     * @ApiBody(sample="{
     *     'id': '1'
     * }")
     * @ApiResponseCode(sample="[200,401,403,404,500]")
     * @ApiParams(name="channelId", type="string", is_required=1, description="Channel ID")
     * @ApiReturn(sample="bool")
     *
     * @param array     $params
     * @param \stdClass $data
     * @param Request   $request
     *
     * @return bool
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     */
    public function actionExportChannel($params, $data, Request $request): bool
    {
        // checking request
        if (!$request->isPost() || empty($data->id)) {
            throw new Exceptions\BadRequest();
        }

        // checking rules
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
}
