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

Espo.define('export:views/export-configurator-item/fields/add-all-locales', 'views/fields/bool',
    Dep => {

        return Dep.extend({

            isFieldMultiLang: false,
            isAttributeMultiLang: false,

            init: function () {
                this.isFieldMultiLang = this.getMetadata().get(`entityDefs.${this.model.get('entity')}.fields.${this.model.get('name')}.isMultilang`) || false;
                this.isAttributeMultiLang = this.model.get('isAttributeMultiLang') || false;

                Dep.prototype.init.call(this);

                this.listenTo(this.model, 'change:type change:name', () => {
                    this.isFieldMultiLang = this.getMetadata().get(`entityDefs.${this.model.get('entity')}.fields.${this.model.get('name')}.isMultilang`) || false;
                    this.isAttributeMultiLang = false;
                    this.reRender();
                });

                this.listenTo(this.model, 'change:attributeId', () => {
                    if (this.model.get('attributeId')) {
                        this.ajaxGetRequest(`Attribute/${this.model.get('attributeId')}`).then(attribute => {
                            this.isAttributeMultiLang = attribute.isMultilang;
                            this.reRender();
                        });
                    } else {
                        this.isAttributeMultiLang = false;
                        this.reRender();
                    }
                });
            },

            afterRender() {
                Dep.prototype.afterRender.call(this);

                if (this.mode !== 'list') {
                    this.checkFieldVisibility();
                }
            },

            checkFieldVisibility() {
                this.hide();

                if (!this.model.get('id') && (this.getConfig().get('inputLanguageList') || []).length > 0) {
                    if (this.model.get('type') === 'Field' && this.isFieldMultiLang) {
                        this.show();
                    }
                    if (this.model.get('type') === 'Attribute' && this.isAttributeMultiLang) {
                        this.show();
                    }
                }
            },

        })
    });