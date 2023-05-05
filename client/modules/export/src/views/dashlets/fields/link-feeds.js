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

Espo.define('export:views/dashlets/fields/link-feeds', 'export:views/fields/int-with-link-to-list',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);
            this.listScope = 'ExportFeed';
        },

        getSearchFilter() {
            let bool = {};

            switch (parseInt(this.model.attributes.interval)) {
                case 1:
                    bool.onlyExportFailed24Hours = true;
                    break;
                case 7:
                    bool.onlyExportFailed7Days = true;
                    break;
                default:
                    bool.onlyExportFailed28Days = true;
                    break;
            }

            return {
                textFilter: '',
                primary: null,
                bool: bool
            };
        }

    })
);
