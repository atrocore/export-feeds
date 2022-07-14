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

Espo.define('export:views/export-configurator-item/fields/filter-field', 'views/fields/enum', function (Dep) {

    return Dep.extend({

        prohibitedEmptyValue: false,

        init: function () {
            Dep.prototype.init.call(this);

            this.listenTo(this.model, 'change:name change:type', () => {
                this.setupOptions();
                this.reRender();
                this.model.set('filterField', null);
            });
        },

        setupOptions() {
            this.params.options = [''];
            this.translatedOptions = {"":""};

            if (this.getMetadata().get(['entityDefs', this.model.get('entity'), 'fields', this.model.get('name'), 'type']) !== 'linkMultiple') {
                return;
            }

            let scope = this.getMetadata().get(['entityDefs', this.model.get('entity'), 'links', this.model.get('name'), 'entity']);

            const fields = this.getMetadata().get(['entityDefs', scope, 'fields']);
            if (fields) {
                $.each(fields, (field, fieldDefs) => {
                    if (fieldDefs.type && ['enum', 'multiEnum'].includes(fieldDefs.type) && this.checkNotStorableField(fieldDefs)) {
                        this.params.options.push(field);
                        this.translatedOptions[field] = this.translate(field, 'fields', scope);
                    }
                });
            }
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

        checkFieldVisibility() {
            if (this.model.get('type') === 'Field' && this.getMetadata().get(['entityDefs', this.model.get('entity'), 'fields', this.model.get('name'), 'type']) === 'linkMultiple') {
                this.$el.parent().show();
            } else {
                this.$el.parent().hide();
            }
        },

    })
});