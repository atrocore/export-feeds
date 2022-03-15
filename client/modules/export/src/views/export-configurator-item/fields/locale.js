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

Espo.define('export:views/export-configurator-item/fields/locale', 'views/fields/enum',
    Dep => {

        return Dep.extend({

            prohibitedEmptyValue: true,

            isAttributeMultiLang: false,

            init: function () {
                this.isAttributeMultiLang = this.model.get('isAttributeMultiLang');

                Dep.prototype.init.call(this);

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

            setupOptions() {
                this.params.options = ['mainLocale'];
                this.translatedOptions = {mainLocale: this.translate('mainLocale', 'labels', 'ExportConfiguratorItem')};

                (this.getConfig().get('inputLanguageList') || []).forEach(locale => {
                    this.params.options.push(locale);
                    this.translatedOptions[locale] = locale;
                });
            },

            afterRender() {
                Dep.prototype.afterRender.call(this);

                if (this.mode !== 'list') {
                    this.checkFieldVisibility();
                }
            },

            checkFieldVisibility() {
                if (this.isAttributeMultiLang && (this.getConfig().get('inputLanguageList') || []).length > 0) {
                    this.show();
                } else {
                    this.hide();
                }
            },

        })
    });