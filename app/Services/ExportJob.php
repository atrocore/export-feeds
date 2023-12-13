<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

declare(strict_types=1);

namespace Export\Services;

use Doctrine\DBAL\ParameterType;
use Espo\Core\Templates\Services\Base;
use Espo\ORM\Entity;

class ExportJob extends Base
{
    protected $mandatorySelectAttributeList = ['exportFeedId', 'exportFeedName', 'state', 'stateMessage'];

    public function deleteOld(): bool
    {
        $days = $this->getConfig()->get('exportJobsMaxDays', 21);
        if ($days === 0) {
            return true;
        }

        // delete
        $toDelete = $this->getEntityManager()->getRepository('ExportJob')
            ->where(['modifiedAt<' => (new \DateTime())->modify("-$days days")->format('Y-m-d H:i:s')])
            ->limit(0, 2000)
            ->order('modifiedAt')
            ->find();
        foreach ($toDelete as $entity) {
            $this->getEntityManager()->removeEntity($entity);
        }

        // delete forever
        $daysToDeleteForever = $days + 14;
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb
            ->delete('export_job')
            ->where('modified_at < :maxDate')
            ->andWhere('deleted = :true')
            ->setParameter('maxDate', (new \DateTime())->modify("-$daysToDeleteForever days")->format('Y-m-d H:i:s'))
            ->setParameter('true', true, ParameterType::BOOLEAN)
            ->executeStatement();

        return true;
    }

    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        if (empty($feed = $entity->get('exportFeed'))) {
            return;
        }

        $entity->set('editable', $this->getAcl()->check($feed, 'edit'));
    }
}
