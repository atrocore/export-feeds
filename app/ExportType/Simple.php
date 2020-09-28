<?php

declare(strict_types=1);

namespace Export\ExportType;

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Json;
use Export\ExportData\Record;

/**
 * Type Simple
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
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
