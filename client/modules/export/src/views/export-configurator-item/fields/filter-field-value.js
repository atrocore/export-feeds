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

Espo.define('export:views/export-configurator-item/fields/filter-field-value', 'views/fields/multi-enum', function (Dep) {

    return Dep.extend({

        init: function () {
            Dep.prototype.init.call(this);

            this.listenTo(this.model, 'change:name change:type change:filterField', () => {
                this.setupOptions();
                this.reRender();
                this.model.set('filterFieldValue', null);
            });
        },

        setupOptions() {
            this.params.options = [];
            this.translatedOptions = {};

            if (this.getMetadata().get(['entityDefs', this.model.get('entity'), 'fields', this.model.get('name'), 'type']) !== 'linkMultiple') {
                return;
            }

            let scope = this.getMetadata().get(['entityDefs', this.model.get('entity'), 'links', this.model.get('name'), 'entity']);

            (this.getMetadata().get(['entityDefs', scope, 'fields', this.model.get('filterField'), 'options']) || []).forEach(option => {
                this.params.options.push(option);
                this.translatedOptions[option] = this.translate(option, 'labels', scope);
            });
        },

        checkNotStorableField(fieldDefs) {
            if (fieldDefs.notStorable !== true) {
                return true;
            }

            return (fieldDefs.relatingEntityField || []).includes(this.model.get('entity'));
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.mode !== 'list') {
                this.checkFieldVisibility();
            }
        },

        isRequired() {
            return this.model.get('type') === 'Field' && this.model.get('filterField');
        },

        checkFieldVisibility() {
            if (this.isRequired()) {
                this.$el.parent().show();
            } else {
                this.$el.parent().hide();
            }
        },

    })
});