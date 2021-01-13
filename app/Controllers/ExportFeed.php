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

        return $this->getRecordService()->exportFile($data->id);
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
}
