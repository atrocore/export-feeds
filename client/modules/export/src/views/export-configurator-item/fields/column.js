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

Espo.define('export:views/export-configurator-item/fields/column', 'views/fields/varchar', function (Dep) {

    return Dep.extend({

        getCellElement: function () {
            return this.$el;
        },

        init: function () {
            Dep.prototype.init.call(this);

            if (!this.model.get('id')) {
                this.prepareValue();
            }

            this.listenTo(this.model, 'change:attributeId change:locale change:columnType', () => {
                if (this.model.get('columnType') !== 'custom') {
                    if (this.model.get('attributeId')) {
                        this.ajaxGetRequest(`Attribute/${this.model.get('attributeId')}`).then(attribute => {
                            let name = 'name';
                            if (this.model.get('columnType') === 'name') {
                                let locale = this.model.get('language');
                                if (locale === 'main') {
                                    locale = '';
                                }

                                if (locale && attribute.isMultilang) {
                                    name = name + locale.charAt(0).toUpperCase() + locale.charAt(1) + locale.charAt(3) + locale.charAt(4).toLowerCase();
                                }
                            }
                            this.model.set('attributeNameValue', attribute[name]);
                        });
                    } else {
                        this.model.set('attributeNameValue', null);
                    }
                }
            });

            this.listenTo(this.model, 'change:name change:attributeNameValue change:columnType change:exportIntoSeparateColumns', () => {
                this.prepareValue();
                this.reRender();
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.mode !== 'list') {
                this.checkFieldVisibility();
                this.checkFieldDisability();
            }
        },

        checkFieldDisability() {
            if (!this.isCustomType()) {
                this.$el.find('input').attr('disabled', 'disabled');
            } else {
                this.$el.find('input').removeAttr('disabled');
            }
        },

        checkFieldVisibility() {
            this.$el.parent().show();
        },

        isCustomType() {
            return this.model.get('columnType') === 'custom';
        },

        prepareValue() {
            if (this.model.get('type') === 'Field') {
                this.prepareFieldValue();
            }

            if (this.model.get('type') === 'Attribute') {
                this.prepareAttributeValue();
            }
        },

        prepareFieldValue() {
            if (this.model.get('columnType') === 'name') {
                let locale = this.getMetadata().get(`entityDefs.${this.model.get('entity')}.fields.${this.model.get('name')}.multilangLocale`);
                if (locale) {
                    let originField = this.getMetadata().get(`entityDefs.${this.model.get('entity')}.fields.${this.model.get('name')}.multilangField`);
                    this.getTranslates(locale, translates => {
                        let columnName = originField;
                        if (translates[this.model.get('entity')] && translates[this.model.get('entity')]['fields'][originField]) {
                            columnName = translates[this.model.get('entity')]['fields'][originField];
                        } else if (translates['Global'] && translates['Global']['fields'][originField]) {
                            columnName = translates['Global']['fields'][originField];
                        }
                        this.model.set('column', columnName);
                    });
                } else {
                    this.model.set('column', this.translate(this.model.get('name'), 'fields', this.model.get('entity')));
                }
            }

            if (this.model.get('columnType') === 'internal') {
                this.model.set('column', this.translate(this.model.get('name'), 'fields', this.model.get('entity')));
            }
        },

        prepareAttributeValue() {
            let language = this.model.get('language');

            if (language === 'main') {
                language = '';
            }

            if (this.model.get('columnType') === 'name') {
                this.model.set('column', this.model.get('attributeNameValue'));
            }

            if (this.model.get('columnType') === 'internal') {
                let name = this.model.get('attributeNameValue');
                if (language) {
                    name = name + ' / ' + language;
                }

                this.model.set('column', name);
            }
        },

        getTranslates(locale, callback) {
            this.ajaxGetRequest(`I18n`, {locale: locale}).then(responseData => {
                callback(responseData);
            });
        },

    })
});