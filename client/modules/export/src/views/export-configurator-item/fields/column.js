/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.md, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
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

            this.listenTo(this.model, 'change:attributeId change:language change:columnType', () => {
                if (this.model.get('columnType') !== 'custom') {
                    if (this.model.get('attributeId')) {
                        this.ajaxGetRequest(`Attribute/${this.model.get('attributeId')}`).then(attribute => {
                            let name = 'name';
                            if (this.model.get('columnType') === 'name') {
                                let language = this.model.get('language');
                                if (language === 'main') {
                                    language = '';
                                }

                                if (language && attribute.isMultilang) {
                                    name = name + language.charAt(0).toUpperCase() + language.charAt(1) + language.charAt(3) + language.charAt(4).toLowerCase();
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
                let columnName = this.translate(this.model.get('name'), 'fields', this.model.get('entity'));

                let language = this.model.get('language');
                if (language === 'main') {
                    language = '';
                }

                if (language) {
                    columnName += ' / ' + language;
                }

                this.model.set('column', columnName);
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