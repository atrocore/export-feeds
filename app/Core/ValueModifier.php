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
