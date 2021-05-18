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

Espo.define('export:views/export-feed/fields/column-type', 'views/fields/enum',
    Dep => {

        return Dep.extend({

            prohibitedEmptyValue: true,

            init: function () {
                Dep.prototype.init.call(this);

                if (!this.model.get('columnType')) {
                    this.model.set('columnType', 'name', {silent: true});
                }

                this.listenTo(this.model, 'change:field change:exportIntoSeparateColumns change:columnType', () => {
                    this.reRender();
                });
            },

            setupOptions() {
                this.params.options = ['name', 'internal', 'custom'];
                this.translatedOptions = {
                    name: this.translate('name', 'columnType', 'ExportFeed'),
                    internal: this.translate('internal', 'columnType', 'ExportFeed'),
                    custom: this.translate('custom', 'columnType', 'ExportFeed'),
                };
            },

            isRequired() {
                return true;
            },

        })
    });