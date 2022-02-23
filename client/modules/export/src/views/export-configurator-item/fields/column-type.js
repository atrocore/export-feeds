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

Espo.define('export:views/export-configurator-item/fields/column-type', 'views/fields/enum',
    Dep => {

        return Dep.extend({

            prohibitedEmptyValue: true,

            init: function () {
                Dep.prototype.init.call(this);

                this.listenTo(this.model, 'change:name change:exportIntoSeparateColumns', () => {
                    this.setupOptions();
                    this.reRender();
                });
            },

            setupOptions() {
                this.params.options = ['name', 'internal', 'custom'];
                this.translatedOptions = {
                    name: this.translate('name', 'columnType', 'ExportConfiguratorItem'),
                    internal: this.translate('internal', 'columnType', 'ExportConfiguratorItem'),
                    custom: this.translate('custom', 'columnType', 'ExportConfiguratorItem'),
                };

                if (this.model.get('exportIntoSeparateColumns')) {
                    this.params.options.push('relatedRecordName');
                    this.translatedOptions['relatedRecordName'] = this.translate('relatedRecordName', 'columnType', 'ExportConfiguratorItem');
                    let relatedEntity = this.getMetadata().get(`entityDefs.${this.model.get('entity')}.links.${this.model.get('name')}.entity`);
                    if (relatedEntity && this.getMetadata().get(`entityDefs.${relatedEntity}.fields.code`)) {
                        this.params.options.push('relatedRecordCode');
                        this.translatedOptions['relatedRecordCode'] = this.translate('relatedRecordCode', 'columnType', 'ExportConfiguratorItem');
                    }
                }
            },

            afterRender() {
                Dep.prototype.afterRender.call(this);

                if (this.mode !== 'list') {
                    this.checkFieldVisibility();
                }
            },

            checkFieldVisibility() {
                if (this.isPavs()) {
                    this.$el.hide();
                } else {
                    this.$el.show();
                }
            },

            isPavs() {
                return this.model.get('entity') === 'Product' && this.model.get('field') === 'productAttributeValues';
            },

        })
    });