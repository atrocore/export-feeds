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

class VarcharType extends AbstractType
{
    public function convertToString(array &$result, array $record, array $configuration): void
    {
        $field = $configuration['field'];
        $column = $configuration['column'];
        $result[$column] = $configuration['nullValue'];

        if (array_key_exists($field,$record)) {
            $value = $record[$field];
            if(($value === null || $value === '') && array_key_exists($configuration['fallbackField'], $record) ){
                $value = $record[$configuration['fallbackField']];
            }

            if ($value === null || $value === '') {
                $result[$column] = $value === null ? $configuration['nullValue'] : $configuration['emptyValue'];
            } else {
                $result[$column] = $value;
            }
        }
    }
}
