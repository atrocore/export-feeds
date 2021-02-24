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

Espo.define('export:views/export-feed/simple-type-components/record/entity-search', 'views/record/search',
    Dep => Dep.extend({

        disableSavePreset: true,

        setup() {
            Dep.prototype.setup.call(this);

            this.presetFilterList = [];
            this.boolFilterList = [];
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            const formGroup = this.$el.find('.search-row > .form-group');
            formGroup.attr('class', formGroup.attr('class').replace(/\bcol-[a-z]{2}-\d+\b/g, ''));
            formGroup.addClass('col-md-12');

            this.$el.find('.search[data-action="search"]').html(`<span class="fa fa-save"></span><span>${this.translate('Save')}</span>`);
        },

        isLeftDropdown() {
            return true;
        },

        refresh() {
            //leave empty
        },

        updateCollection() {
            this.trigger('saveEntityFilter')
        }

    })
);
