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

namespace Export\ExportType;

use Espo\Core\Utils\Json;

/**
 * Class ProductImage
 */
class ProductAsset extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function getData(): array
    {
        // prepare result
        $result = [];

        // prepare data
        $data = Json::decode(Json::encode($this->getFeed()['data']), true);

        // prepare query
        if (isset($data['where'])) {
            $this->setQuery(['where' => $data['where']]);
        }

        // get entities
        $products = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->find($this->getSelectParams());

        // get header row
        $header = $this->getHeader();

        if (count($products) > 0) {
            foreach ($products as $product) {
                $images = $this->getDBProductAsset($product->get('id'));
                // prepare productData
                $productData = $header;
                // set sku
                $productData['sku'] = $product->get('sku');
                // set images
                foreach ($productData as $channelName => $data) {
                    if (!empty($images[$channelName])) {
                        $productData[$channelName] = implode(';', $images[$channelName]);
                    }
                }

                $result[] = $productData;
            }
        } else {
            $result[] = $header;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getCount(): int
    {
        return $this->getEntityManager()->getRepository('Product')->count($this->getSelectParams());
    }

    /**
     * Get header
     *
     * @return array
     */
    protected function getHeader(): array
    {
        $result = ['sku' => null, 'Global' => null];

        $channels = $this
            ->getEntityManager()
            ->getRepository('Channel')
            ->select(['name'])
            ->where(['isActive' => true])
            ->find();

        foreach ($channels as $channel) {
            $result[$channel->get('name')] = null;
        }

        return $result;
    }

    /**
     * Get images for product from db
     *
     * @param string $productId
     *
     * @return array
     */
    public function getDBProductAsset(string $productId): array
    {
        $url = $this->getEntityManager()->getContainer()->get('Config')->getSiteUrl();
        $customWhere = $this->getCustomWhere();
        $sql = "
            SELECT 
               CASE
                    WHEN ar.scope = 'Channel' THEN c.name
                    ELSE 'Global'
                END AS scope,
                CASE
                    WHEN a.type = 'Gallery Image' THEN CONCAT('{$url}?entryPoint=preview&size=original&id=', a.id)
                    ELSE CONCAT('{$url}?entryPoint=download&id=', a.file_id)
                END AS url
            FROM asset_relation ar
            LEFT JOIN asset a ON ar.asset_id = a.id
            LEFT JOIN asset_relation_channel arc ON arc.asset_relation_id = ar.id AND arc.deleted = 0
            LEFT JOIN channel AS c ON c.id = arc.channel_id AND c.deleted = 0
            WHERE ar.entity_id = :productId
                AND ar.entity_name = 'Product'
                AND ar.deleted = '0'    
                {$customWhere}
            ORDER BY ar.sort_order ASC, ar.modified_at DESC;";

        return $this
            ->getEntityManager()
            ->nativeQuery($sql, ['productId' => $productId])
            ->fetchAll(\PDO::FETCH_COLUMN|\PDO::FETCH_GROUP);;
    }

    protected function getCustomWhere(): string
    {
        return '';
    }

    /**
     * Get select params
     *
     * @return array
     */
    protected function getSelectParams(): array
    {
        return $this
            ->getSelectManager('Product')
            ->getSelectParams($this->getQuery(), true, true);
    }
}
