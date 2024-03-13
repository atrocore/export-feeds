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

use Atro\Core\Migration\Base;

class V1Dot7Dot62 extends Base
{
    public function up(): void
    {
        // clear system ui handlers
        $this->getConnection()
            ->createQueryBuilder()
            ->delete('ui_handler')
            ->where('hash is not null')
            ->executeStatement();
    }

    public function down(): void
    {

    }
}
