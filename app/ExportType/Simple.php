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

namespace Export\ExportType;

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Json;
use Export\ExportData\Record;

/**
 * Type Simple
 */
class Simple extends AbstractType
{
    /**
     * @var array
     */
    protected $productAttributes = [];

    /**
     * @inheritdoc
     *
     * @throws Error
     */
    public function getData(): array
    {
        // prepare result
        $result = [];

        // prepare export feed data
        $data = $this->getFeedData();

        // entities
        $entities = $this->getEntities();

        // get prepare data class
        $dataPrepare = $this->getPrepareDataClass($data['entity']);

        if (!empty($entities)) {
            // prepare result
            foreach ($entities as $entity) {
                $result[$entity->get('id')] = [];

                foreach ($data['configuration'] as $row) {
                    $result[$entity->get('id')]
                        = array_merge($result[$entity->get('id')], $dataPrepare->prepare($entity, $row, $data['delimiter']));
                }
            }
            $result = array_values($result);
        }

        if (empty($result)) {
            foreach ($data['configuration'] as $row) {
                $result[0][$row['column']] = '';
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getCount(): int
    {
        return $this->getEntityManager()->getRepository($this->getFeed()['entity'])->count($this->getSelectParams());
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function getEntities()
    {
        $data = $this->getFeedData();

        // prepare query
        if (isset($data['where'])) {
            $this->setQuery(['where' => $data['where']]);
        }

        // get entities
        return $this
            ->getEntityManager()
            ->getRepository($data['entity'])
            ->find($this->getSelectParams());
    }

    /**
     * @param string $entityName
     *
     * @return Record
     *
     * @throws Error
     */
    protected function getPrepareDataClass(string $entityName): Record
    {
        $prepareDataClassName = "Export\\ExportData\\" . $entityName;

        if (!class_exists($prepareDataClassName)) {
            $prepareDataClassName = "Export\\ExportData\\Record";
        }

        return (new $prepareDataClassName())->setContainer($this->getContainer());
    }

    /**
     * @return array
     */
    protected function getFeedData(): array
    {
        return Json::decode(Json::encode($this->getFeed()['data']), true);
    }
}
