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
 *
 * This software is not allowed to be used in Russia and Belarus.
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
