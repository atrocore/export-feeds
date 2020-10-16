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
