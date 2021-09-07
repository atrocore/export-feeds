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

namespace Export;

use Espo\Core\OpenApiGenerator;
use Treo\Core\ModuleManager\AbstractModule;

/**
 * Class Module
 */
class Module extends AbstractModule
{
    /**
     * @inheritdoc
     */
    public static function getLoadOrder(): int
    {
        return 5140;
    }

    public function prepareApiDocs(array &$data, array $schemas): void
    {
        parent::prepareApiDocs($data, $schemas);

        $data['paths']["/ExportFeed/action/exportFile"]['post'] = [
            'tags'        => ['ExportFeed'],
            "summary"     => "Export data to file",
            "description" => "Export data to file",
            "operationId" => "exportFile",
            'security'    => [['Authorization-Token' => []]],
            'requestBody' => [
                'required' => true,
                'content'  => [
                    'application/json' => [
                        'schema' => [
                            "type"       => "object",
                            "properties" => [
                                "id" => [
                                    "type" => "string",
                                ],
                            ],
                        ]
                    ]
                ],
            ],
            "responses"   => OpenApiGenerator::prepareResponses(["type" => "boolean"]),
        ];

        $data['paths']["/ExportFeed/action/exportChannel"]['post'] = [
            'tags'        => ['ExportFeed'],
            "summary"     => "Export channel data to file",
            "description" => "Export channel data to file",
            "operationId" => "exportChannel",
            'security'    => [['Authorization-Token' => []]],
            'requestBody' => [
                'required' => true,
                'content'  => [
                    'application/json' => [
                        'schema' => [
                            "type"       => "object",
                            "properties" => [
                                "id" => [
                                    "type" => "string",
                                ],
                            ],
                        ]
                    ]
                ],
            ],
            "responses"   => OpenApiGenerator::prepareResponses(["type" => "boolean"]),
        ];
    }
}
