<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore GmbH (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

namespace Export\Migrations;

use Atro\Core\Migration\Base;

class V1Dot7Dot66 extends Base
{
    public function up(): void
    {
        $qb = $this->getConnection()
            ->createQueryBuilder()
            ->update('export_configurator_item')
            ->set('name',':newName')
            ->where('name=:name')
            ->setParameter('newName','extensibleEnums')
            ->setParameter('name','extensibleEnum');

        if($this->isPgSQL()){
            $qb->andWhere("export_feed_id IN (SELECT id FROM export_feed WHERE  data::jsonb @> :entity::jsonb )")
                ->setParameter('entity','{"feedFields": {"entity": "ExtensibleEnumOption"}}');
        }else {
            $qb->andWhere("export_feed_id IN (SELECT id FROM export_feed 
                                    WHERE  JSON_CONTAINS(data, :entity, '$.feedFields.entity') = 1 )")
                ->setParameter('entity','"ExtensibleEnumOption"');
        }

        $qb->executeQuery();
    }

    public function down(): void
    {
        $qb = $this->getConnection()
            ->createQueryBuilder()
            ->update('export_configurator_item')
            ->set('name',':newName')
            ->where('name=:name')
            ->setParameter('newName','extensibleEnum')
            ->setParameter('name','extensibleEnums');

        if($this->isPgSQL()){
            $qb->andWhere("export_feed_id IN (SELECT id FROM export_feed WHERE  data::jsonb @> :entity::jsonb )")
                ->setParameter('entity','{"feedFields": {"entity": "ExtensibleEnumOption"}}');
        }else {
            $qb->andWhere("export_feed_id IN (SELECT id FROM export_feed 
                                    WHERE  JSON_CONTAINS(data, :entity, '$.feedFields.entity') = 1 )")
                ->setParameter('entity','"ExtensibleEnumOption"');
        }

        $qb->executeQuery();
    }


}
