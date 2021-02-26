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

Espo.define('export:views/export-feed/simple-type-components/record/product-search', ['pim:views/product/record/search', 'export:views/export-feed/simple-type-components/record/entity-search'], function (Dep, Filter) {
        return Dep.extend({

            disableSavePreset: true,

            setup() {
                Dep.prototype.setup.call(this);

                this.presetFilterList = [];
                this.boolFilterList = [];
            },

            afterRender() {
                Dep.prototype.afterRender.call(this);

                Filter.prototype.afterRenderExportFilterPanel.call(this);
            },

            isLeftDropdown() {
                return Filter.prototype.isLeftDropdown.call(this);
            },

            resetFilters() {
                return Filter.prototype.resetFilters.call(this);
            },

            refresh() {
                return Filter.prototype.refresh.call(this);
            },

            updateCollection() {
                return Filter.prototype.updateCollection.call(this);
            },

            setFilterDetailMode() {
                return Filter.prototype.setFilterDetailMode.call(this);
            },

            setFilterMode() {
                return Filter.prototype.setFilterMode.call(this);
            },

            getDetailView() {
                return Filter.prototype.getDetailView.call(this);
            },

        })
    }
);
