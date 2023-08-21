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

Espo.define('export:views/export-configurator-item/fields/file-name-template', ['views/fields/script', 'export:views/export-configurator-item/fields/zip'],
    (Dep, ZipField) => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:name change:type change:zip', () => {
                this.reRender();
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            let hasZip = ZipField.prototype.hasZip.call(this);
            if (hasZip && this.model.get('zip')) {
                this.show();
            } else {
                this.hide();
            }
        },

    })
);