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

namespace Export\FieldConverters;

use Espo\ORM\Entity;

class ExtensibleEnumType extends LinkType
{
    protected function getFieldName(string $field): string
    {
        return $field;
    }

    protected function getForeignEntityName(string $entity, string $field): string
    {
        return 'ExtensibleEnumOption';
    }

    protected function needToCallForeignEntity(array $exportBy): bool
    {
        return true;
    }

    public function getEntity(string $scope, string $id): ?Entity
    {
        return $this->convertor->getService('ExtensibleEnumOption')->getEntity($id);
    }
}
