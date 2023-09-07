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

namespace Export\ValueModifiers;

interface ValueModifierInterface
{
    /**
     * @param mixed $context
     *
     * @return void
     */
    public function validate($context = null): void;

    /**
     * @param mixed $value
     * @param mixed $context
     *
     * @return mixed
     */
    public function modify($value, $context = null);
}
