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

Espo.define('export:views/export-configurator-item/fields/language', 'views/fields/enum',
    Dep => {

        return Dep.extend({

            prohibitedEmptyValue: true,

            isMultiLang: false,

            init: function () {
                this.prepareMultiLangParam();

                Dep.prototype.init.call(this);

                this.listenTo(this.model, 'change:type change:name change:attributeId', () => {
                    this.prepareMultiLangParam();
                });
            },

            setupOptions() {
                this.params.options = ['main'];
                this.translatedOptions = {"main": this.getLanguage().translateOption('main', 'languageFilter', 'Global')};

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

            prepareMultiLangParam() {
                if (this.model.get('exportFeedLanguage')) {
                    this.isMultiLang = false;
                    this.reRender();
                    return;
                }

                if (this.model.get('type') === 'Field') {
                    this.isMultiLang = this.getMetadata().get(`entityDefs.${this.model.get('entity')}.fields.${this.model.get('name')}.isMultilang`);
                    this.reRender();
                } else if (this.model.get('type') === 'Attribute') {
                    if (this.model.get('attributeId')) {
                        this.ajaxGetRequest(`Attribute/${this.model.get('attributeId')}`).then(attribute => {
                            this.isMultiLang = attribute.isMultilang;
                            this.reRender();
                        });
                    } else {
                        this.isMultiLang = false;
                        this.reRender();
                    }
                }
            },

            checkFieldVisibility() {
                if (this.isMultiLang && (this.getConfig().get('inputLanguageList') || []).length > 0) {
                    this.show();
                } else {
                    this.hide();
                }
            },

        })
    });