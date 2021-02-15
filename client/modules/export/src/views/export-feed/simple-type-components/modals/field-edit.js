

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
                        entity: this.scope,
                        scope: 'Global'
                    });
                }
            });

            this.entityFields = this.options.entityFields || {};
            this.selectedFields = this.options.selectedFields || [];
            this.setAllowedFields();

            this.createBaseFields();

            this.listenTo(this.model, 'change:field', () => {
                this.applyDynamicChanges();
            });

            this.listenTo(this.model, 'change:scope change:channelId', () => {
                this.model.set({column: this.getColumnFromCategory()});
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.applyDynamicChanges();
        },

        setAllowedFields() {
            this.allowedFields = Object.keys(this.entityFields).filter(item => !this.selectedFields.includes(item));
            let currentField = this.model.get('field');
            if (currentField && !this.allowedFields.includes(currentField)) {
                this.allowedFields.unshift(currentField);
            }
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
                params: {
                    options: this.allowedFields,
                    translatedOptions: this.allowedFields.reduce((prev, curr) => {
                        prev[curr] = this.translate(curr, 'fields', this.scope);
                        return prev;
                    }, {'id': this.translate('id', 'fields', 'Global')})
                }
            }, view => {});

            this.createView('column', 'export:views/export-feed/fields/column', {
                model: this.model,
                name: 'column',
                el: `${this.options.el} .field[data-name="column"]`,
                mode: 'edit',
                params: {
                    required: true
                }
            }, view => {});

            this.createView('exportBy', 'export:views/export-feed/fields/export-by', {
                model: this.model,
                name: 'exportBy',
                el: `${this.options.el} .field[data-name="exportBy"]`,
                mode: 'edit',
                params: {
                    options: [],
                    translatedOptions: {}
                }
            }, view => {});

            this.createView('scope', 'views/fields/enum', {
                model: this.model,
                name: 'scope',
                el: `${this.options.el} .field[data-name="scope"]`,
                mode: 'edit',
                params: {
                    options: ['Global', 'Channel']
                }
            }, view => {
                this.listenTo(this.model, 'change:scope', () => {
                    this.checkChannelVisibility();
                });
            });

            this.createView('channel', 'export:views/export-feed/fields/channel', {
                model: this.model,
                name: 'channel',
                el: `${this.options.el} .field[data-name="channel"]`,
                mode: 'edit',
                params: {
                    required: true
                },
                labelText: this.translate('channel', 'scopeNames', 'Global'),
                createDisabled: true
            }, view => {});
        },

        applyDynamicChanges() {
            this.model.set({column: this.getColumnFromCategory()});
            let column = this.getView('column');
            if (column) {
                column.reRender();
            }
            this.checkScopeVisibility();
            this.checkChannelVisibility();
        },

        getColumnFromCategory() {
            let column = this.translate(this.model.get('field'), 'fields', this.scope);
            if (this.model.get('entity') === 'Product' && this.model.get('field') === 'productCategories') {
                let channelName = this.model.get('channelName');
                if (this.model.get('scope') === 'Channel' && channelName) {
                    column += ` (Channel: ${channelName})`;
                } else {
                    column += ` (${this.model.get('scope')})`;
                }
            }
            return column;
        },

        checkChannelVisibility() {
            let channel = this.getView('channel');
            if (this.model.get('scope') === 'Channel') {
                channel.params.required = true;
                channel.show();
            } else {
                channel.params.required = false;
                channel.hide();
            }
            channel.reRender();
        },

        checkScopeVisibility() {
            let scope = this.getView('scope');
            if (this.model.get('entity') === 'Product' && this.model.get('field') === 'productCategories') {
                scope.show();
            } else {
                scope.hide();
            }
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
