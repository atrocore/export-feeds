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

namespace Export\TwigFunction;

use Espo\Core\Injectable;

abstract class AbstractTwigFunction extends \Atro\Core\Twig\AbstractTwigFunction
{
    protected array $feedData;

    public function setFeedData(array $feedData): void
    {
        $this->feedData = $feedData;
    }

    public function getFeedData(): array
    {
        return $this->feedData;
    }
}
