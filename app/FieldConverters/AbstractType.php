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

use Export\DataConvertor\Convertor;

abstract class AbstractType
{
    protected Convertor $convertor;

    public function __construct(Convertor $convertor)
    {
        $this->convertor = $convertor;
    }

    abstract public function convertToString(array &$result, array $record, array $configuration): void;

    public function applyValueModifiers(array $configuration, &$value): void
    {
        if (!empty($configuration['valueModifier'])) {
            $this->convertor->getValueModifier()->apply($configuration['valueModifier'], $value);
        }
    }
}
