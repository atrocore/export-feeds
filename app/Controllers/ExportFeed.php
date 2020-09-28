<?php

declare(strict_types=1);

namespace Export\Controllers;

use Espo\Core\Templates\Controllers\Base;
use Espo\Core\Exceptions;
use Slim\Http\Request;

/**
 * ExportFeed controller
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ExportFeed extends Base
{

    /**
     * @ApiDescription(description="Export data to file")
     * @ApiMethod(type="POST")
     * @ApiRoute(name="/ExportFeed/{exportFeedId}/exportByFeed")
     * @ApiResponseCode(sample="[200,401,403,404,500]")
     * @ApiParams(name="exportFeedId", type="string", is_required=1, description="ExportFeed ID")
     * @ApiReturn(sample="bool")
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return bool
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Error
     * @throws Exceptions\Forbidden
     */
    public function actionExportFile($params, $data, Request $request): bool
    {
        // checking request
        if (!$request->isPost()) {
            throw new Exceptions\BadRequest();
        }

        // checking rules
        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Exceptions\Forbidden();
        }

        if (!empty($params['exportFeedId'])) {
            return $this->getRecordService()->exportFile($params['exportFeedId']);
        }

        throw new Exceptions\Error();
    }

    /**
     * @ApiDescription(description="Export channel data to file")
     * @ApiMethod(type="POST")
     * @ApiRoute(name="/ExportFeed/{channelId}/exportByChannel")
     * @ApiResponseCode(sample="[200,401,403,404,500]")
     * @ApiParams(name="channelId", type="string", is_required=1, description="Channel ID")
     * @ApiReturn(sample="bool")
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return bool
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Error
     * @throws Exceptions\Forbidden
     */
    public function actionExportChannel($params, $data, Request $request): bool
    {
        // checking request
        if (!$request->isPost()) {
            throw new Exceptions\BadRequest();
        }

        // checking rules
        if (!$this->getAcl()->check($this->name, 'read') || !$this->getAcl()->check('Channel', 'read')) {
            throw new Exceptions\Forbidden();
        }

        if (!empty($params['channelId'])) {
            return $this->getRecordService()->exportChannel($params['channelId']);
        }

        throw new Exceptions\Error();
    }
}
