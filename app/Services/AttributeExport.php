<?php
declare(strict_types=1);

namespace Export\Services;

use Export\ExportType\ExportTypeFactory;
use PDO;

/**
 * Class AttributeExport
 * @package Export\Services
 */
class AttributeExport extends AbstractService
{
    /**
     * @param array $data
     *
     * @return bool
     */
    public function configHasAttributes(array $data): bool
    {
        foreach ($data as $datum) {
            if (isset($datum['attributeId'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $data
     * @return array
     */
    public function getAttributesConfig(array $data): array
    {
        $entities = $this->getEntities($data);

        $familyIds = array_unique(array_map(function ($item) {
            if (isset($item['productFamilyId'])) {
                return $item['productFamilyId'];
            }
        }, $entities->getInnerContainer()));

        return array_map(function ($item) {
            $columnName = "{$item['attributeName']} ("
                . ($item['scope'] === "Global" ? "Global" : "Channel : {$item['channelName']}")
                . ")";

            $res = [
                "entity"        => "Attribute",
                "scope"         => $item['scope'],
                "attributeName" => $item['attributeName'],
                "attributeId"   => $item['attributeId'],
                "column"        => $columnName,
                "channelId"     => $item['channelId'],
                "channelName"   => $item['channelName'],
            ];

            return $res;
        }, $this->getAttributesData($familyIds));
    }

    /**
     * @param array $data
     * @return object
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function getEntities(array $data): object
    {
        return $this
            ->getExportTypeFactory()
            ->create($data['feed']['type'])
            ->setFeed($data['feed'])
            ->getEntities();
    }

    /**
     * @return ExportTypeFactory
     */
    protected function getExportTypeFactory(): ExportTypeFactory
    {
        return $this->getContainer()->get('exportTypeFactory');
    }

    /**
     * @param array $familyIds
     * @return array
     */
    protected function getAttributesData(array $familyIds): array
    {
        if (!$familyIds) {
            return [];
        }

        $pdo = $this->getEntityManager()->getPDO();

        $sql = 'select
                       pfa.scope, a.name as attributeName,
                       c.name as channelName,
                       a.id as attributeId,
                       c.id as channelId
                from product_family as pf
                inner join product_family_attribute as pfa
                    ON (pf.id = pfa.product_family_id)
                inner join attribute as a
                    ON (pfa.attribute_id = a.id)
                left join product_family_attribute_channel as pfac
                    ON (pfa.id = pfac.product_family_attribute_id)
                left join channel as c
                    ON (pfac.channel_id = c.id)
                where pf.id in ("' . implode('","', $familyIds) . '") ORDER BY a.name ASC';

        $res = $pdo->query($sql);

        return $res->fetchAll(PDO::FETCH_ASSOC);
    }
}