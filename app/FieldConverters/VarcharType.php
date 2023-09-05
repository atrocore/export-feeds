<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.md, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

declare(strict_types=1);

namespace Export\FieldConverters;

class VarcharType extends AbstractType
{
    public function convertToString(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $column = $configuration['column'];

        $result[$column] = $configuration['nullValue'];
        if (isset($record[$field])) {
            if (empty($record[$field])) {
                $result[$column] = $record[$field] === null ? $configuration['nullValue'] : $configuration['emptyValue'];
            } else {
                $result[$column] = $record[$field];
                $this->applyValueModifiers($configuration, $result[$column]);
            }
        }
    }
}
