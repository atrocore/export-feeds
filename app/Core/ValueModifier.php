<?php
/*
 * Export Feeds
 * Free Extension
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Export\Core;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Injectable;
use Export\ValueModifiers\ValueModifierInterface;

class ValueModifier extends Injectable
{
    public function __construct()
    {
        $this->addDependency('container');
    }

    public function apply(array $valueModifiers, &$value = null)
    {
        if (empty($valueModifiers)) {
            return;
        }

        $metadata = $this->getInjection('container')->get('metadata');
        $language = $this->getInjection('container')->get('language');

        foreach ($valueModifiers as $modifierName) {
            if (empty($modifierName) || $modifierName === '[]') {
                continue;
            }

            $context = [];
            if (preg_match_all("/^([\w_]+)\((.*)\)$/", $modifierName, $matches)) {
                $modifierName = $matches[1][0];
                if (!empty($matches[2][0])) {
                    $context = $matches[2][0];
                }
            }

            $unknownModifierMessage = sprintf($language->translate('unknownModifier', 'exceptions', 'ExportConfiguratorItem'), $modifierName);

            $className = $metadata->get(['export', 'valueModifiers', 'modifiers', $modifierName]);
            if (empty($className)) {
                throw new BadRequest($unknownModifierMessage);
            }

            $modifier = $this->getInjection('container')->get($className);
            if (!$modifier instanceof ValueModifierInterface) {
                throw new BadRequest($unknownModifierMessage);
            }

            $modifier->validate($context);

            if ($value !== null) {
                $value = $modifier->modify($value, $context);
            }
        }
    }
}
