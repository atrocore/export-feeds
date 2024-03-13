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

declare(strict_types=1);

namespace Export\Migrations;

/**
 * Class V1Dot1Dot6
 */
class V1Dot1Dot6 extends V1Dot1Dot5
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $this->execute("DELETE FROM export_feed WHERE 1");
        $this->execute("DELETE FROM export_result WHERE 1");
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        $this->up();
    }
}
