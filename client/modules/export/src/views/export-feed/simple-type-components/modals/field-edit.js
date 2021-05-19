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

Espo.define('export:views/export-feed/simple-type-components/modals/field-edit', 'views/modal',
    Dep => Dep.extend({

        template: 'export:export-feed/simple-type-components/modals/field-edit',

        allowedFields: [],

        setup() {
            Dep.prototype.setup.call(this);

            this.scope = this.options.scope;

            this.buttonList.push({
                    name: 'save',
                    label: 'Save',
                    style: 'primary'
                },
                {
                    name: 'cancel',
                    label: 'Cancel'
                });

            this.id = this.options.id;

            if (!this.id) {
                this.header = this.getLanguage().translate('Create', 'labels', this.scope);
            } else {
                this.header = this.getLanguage().translate('Edit');
                this.initialAttributes = this.model.getClonedAttributes();
            }

            this.getModelFactory().create(null, model => {
                if (this.model) {
                    model = this.model.clone();
                    model.id = this.model.id;
                    model.defs = this.model.defs;
                    this.model = model;
                } else {
                    this.model = model;
                    this.model.set({
                        entity: this.scope
                    });
                }
            });

            this.entityFields = this.options.entityFields || {};
            this.selectedFields = this.options.selectedFields || [];
            this.setAllowedFields();

            this.createBaseFields();
        },

        setAllowedFields() {
            this.allowedFields = ['id'].concat(Object.keys(this.entityFields));
        },

        createBaseFields() {
            if (!this.id) {
                this.model.set({field: this.allowedFields[0]});
            }

            this.createView('field', 'views/fields/enum', {
                model: this.model,
                name: 'field',
                el: `${this.options.el} .field[data-name="field"]`,
                mode: 'edit',
                prohibitedEmptyValue: true,
                params: {
                    options: this.allowedFields,
                    translatedOptions: this.allowedFields.reduce((prev, curr) => {
                        prev[curr] = this.translate(curr, 'fields', this.scope);
                        return prev;
                    }, {'id': this.translate('id', 'fields', 'Global')})
                }
            });

            this.createView('columnType', 'export:views/export-feed/fields/column-type', {
                model: this.model,
                configurator: this.options.configurator,
                name: 'columnType',
                el: `${this.options.el} .field[data-name="columnType"]`,
                mode: 'edit'
            });

            this.createView('column', 'export:views/export-feed/fields/column', {
                model: this.model,
                configurator: this.options.configurator,
                translates: this.options.translates,
                name: 'column',
                el: `${this.options.el} .field[data-name="column"]`,
                mode: 'edit'
            });

            this.createView('exportBy', 'export:views/export-feed/fields/export-by', {
                model: this.model,
                name: 'exportBy',
                labelText: this.translate('exportBy', 'fields', 'ExportFeed'),
                el: `${this.options.el} .field[data-name="exportBy"]`,
                mode: 'edit',
                params: {
                    options: [],
                    translatedOptions: {}
                }
            });

            this.createView('exportIntoSeparateColumns', 'export:views/export-feed/fields/separate-columns', {
                model: this.model,
                name: 'exportIntoSeparateColumns',
                el: `${this.options.el} .field[data-name="exportIntoSeparateColumns"]`,
                mode: 'edit',
            });

            this.createView('attributeColumn', 'export:views/export-feed/fields/attribute-column', {
                model: this.model,
                name: 'attributeColumn',
                el: `${this.options.el} .field[data-name="attributeColumn"]`,
                mode: 'edit',
                prohibitedEmptyValue: true,
                params: {
                    options: ["attributeName", "internalAttributeName", "attributeCode"],
                    translatedOptions: {
                        "attributeName": this.translate("attributeName", "labels", "ExportFeed"),
                        "internalAttributeName": this.translate("internalAttributeName", "labels", "ExportFeed"),
                        "attributeCode": this.translate("attributeCode", "labels", "ExportFeed")
                    }
                }
            });
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
