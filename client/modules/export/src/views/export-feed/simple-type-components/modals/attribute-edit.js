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

Espo.define('export:views/export-feed/simple-type-components/modals/attribute-edit', 'export:views/export-feed/simple-type-components/modals/field-edit',
    Dep => Dep.extend({

        template: 'export:export-feed/simple-type-components/modals/attribute-edit',

        createBaseFields() {
            this.createView('attribute', 'views/fields/link', {
                model: this.model,
                name: 'attribute',
                el: `${this.options.el} .field[data-name="attribute"]`,
                mode: 'edit',
                params: {
                    required: true
                },
                foreignScope: 'Attribute',
                createDisabled: true
            }, view => {
            });

            this.createView('columnType', 'export:views/export-feed/fields/column-type', {
                model: this.model,
                configurator: this.options.configurator,
                name: 'columnType',
                el: `${this.options.el} .field[data-name="columnType"]`,
                mode: 'edit'
            });

            this.createView('locale', 'export:views/export-feed/fields/locale', {
                model: this.model,
                name: 'locale',
                el: `${this.options.el} .field[data-name="locale"]`,
                mode: 'edit'
            });

            this.createView('column', 'export:views/export-feed/fields/column', {
                model: this.model,
                name: 'column',
                el: `${this.options.el} .field[data-name="column"]`,
                mode: 'edit'
            }, view => {
            });
        },

        setAllowedFields() {
        },

        applyDynamicChanges() {
        },

        actionSave() {
            if (!this.validate()) {
                let data = {};
                let fields = this.nestedViews;
                for (let i in fields) {
                    let view = fields[i];
                    if (!view.disabled && !view.readOnly && view.isFullyRendered()) {
                        _.extend(data, view.fetch());
                    }
                }
                this.model.set(data, {silent: true});
                this.trigger('after:save', this.model);

                // auto-create locales attributes
                if (this.options.isAdd) {
                    this.ajaxGetRequest(`Attribute/${this.model.get('attributeId')}`).then(attribute => {
                        if (attribute.isMultilang && data.locale === 'mainLocale') {
                            (this.getConfig().get('inputLanguageList') || []).forEach(locale => {
                                this.getModelFactory().create(null, model => {
                                    let localeData = _.extend({}, data);
                                    localeData.locale = locale;

                                    if (localeData.columnType === 'custom') {
                                        localeData.columnType = 'name';
                                    }

                                    let exists = false;
                                    (this.options.collection.models || []).forEach(m => {
                                        if (m.get('attributeId') + m.get('locale') === localeData.attributeId + localeData.locale) {
                                            exists = true;
                                        }
                                    });

                                    if (!exists) {
                                        model.set(localeData, {silent: true});
                                        this.options.collection.trigger('configuration-update', model);
                                    }
                                });
                            });
                        }
                    });
                }

                this.dialog.close();
            }
        },

        validate() {
            let notValid = false;
            let fields = this.nestedViews;
            for (let i in fields) {
                notValid = fields[i].validate() || notValid;
            }
            return notValid
        }

    })
);
