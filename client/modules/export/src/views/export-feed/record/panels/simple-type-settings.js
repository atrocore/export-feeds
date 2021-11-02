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

Espo.define('export:views/export-feed/record/panels/simple-type-settings', 'views/record/panels/bottom',
    Dep => Dep.extend({

        template: 'export:export-feed/record/panels/simple-type-settings',

        configListView: 'export:views/export-feed/simple-type-components/record/simple-type-list',

        configRowActionsView: 'export:views/export-feed/simple-type-components/record/panels/row-actions/custom-edit',

        configFieldEditView: 'export:views/export-feed/simple-type-components/modals/field-edit',

        configAttributeEditView: 'export:views/export-feed/simple-type-components/modals/attribute-edit',

        configuratorFields: ['entity', 'delimiter', 'allFields', 'emptyValue', 'nullValue', 'markForNotLinkedAttribute'],

        validations: ['configurator', 'delimiters'],

        initialData: null,

        configData: null,

        translates: {},

        events: _.extend({
            'click button[data-name="configuratorActions"]': function (e) {
                let actions = this.getConfiguratorActions();
                if (actions.length === 1) {
                    e.stopPropagation();
                    this.actionAddEntityField();
                }
            }
        }, Dep.prototype.events),

        data() {
            let data = Dep.prototype.data.call(this);
            data.scope = this.model.name;
            data.configuratorActions = this.getConfiguratorActions();

            return data;
        },

        setup() {
            Dep.prototype.setup.call(this);

            const inputLanguageList = this.getConfig().get('inputLanguageList') || [];

            if (inputLanguageList.length > 0) {
                inputLanguageList.forEach(locale => {
                    this.ajaxGetRequest(`I18n`, {locale: locale}, {async: false}).then(responseData => {
                        this.translates[locale] = responseData;
                    });
                });
            }

            this.loadConfiguration();
            this.initialData = Espo.Utils.cloneDeep(this.configData);
            this.createConfiguratorFields();
            this.createConfiguratorList();
        },

        getConfiguratorActions() {
            let configuratorActions = [];
            if (this.getAcl().check('ExportFeed', 'edit')) {
                configuratorActions.push({
                    action: 'addEntityField',
                    label: this.translate('addEntityField', 'labels', 'ExportFeed')
                });
                if (this.panelModel.get('entity') === 'Product') {
                    configuratorActions.push({
                        action: 'addProductAttribute',
                        label: this.translate('addProductAttribute', 'labels', 'ExportFeed')
                    });
                }
            }
            return configuratorActions;
        },

        loadConfiguration() {
            this.configData = this.model.get('data');
            this.entitiesList = this.getEntitiesList();
            this.entityFields = this.getEntityFields(this.configData.entity);

            this.setupSelected();
        },

        getEntitiesList() {
            let scopes = this.getMetadata().get('scopes') || {};
            return Object.keys(scopes)
                .filter(scope => scopes[scope].entity && scopes[scope].object && scopes[scope].customizable)
                .sort((v1, v2) => this.translate(v1, 'scopeNames').localeCompare(this.translate(v2, 'scopeNames')));
        },

        getEntityFields(entity) {
            let result = {};
            let notExportedType = [
                'linkParent',
                'currencyConverted',
                'available-currency',
                'file',
                'attachmentMultiple'
            ];
            if (entity) {
                let fields = this.getMetadata().get(['entityDefs', entity, 'fields']);
                Object.keys(fields).forEach(name => {
                    let field = fields[name];
                    if (!notExportedType.includes(field.type) && (name === 'code' || !field.emHidden) && !field.disabled && !field.exportDisabled) {
                        result[name] = fields[name];
                    }
                });
            }
            return result;
        },

        setupSelected() {
            this.selectedFields = ((this.configData || {}).configuration || [])
                .filter(item => !item.attributeId && !(this.entityFields[item.field] || {}).exportMultipleField)
                .map(item => item.field);
        },

        createConfiguratorFields() {
            this.getModelFactory().create(null, model => {
                this.panelModel = model;
                this.updatePanelModelAttributes();
                this.loadAllFields()

                this.listenTo(this.panelModel, 'change:entity', () => {
                    this.configData.entity = this.panelModel.get('entity');
                    this.entitiesList = this.getEntitiesList();
                    this.entityFields = this.getEntityFields(this.configData.entity);
                    this.panelModel.set('allFields', false, {silent: true});
                    this.panelModel.set('allFields', true);
                    this.model.trigger('configuration-entity-changed', this.panelModel.get('entity'));
                });

                this.listenTo(this.panelModel, 'change:allFields', () => {
                    this.configData.allFields = this.panelModel.get('allFields');
                    this.loadAllFields();
                    this.createConfiguratorList();
                });

                let entityTranslatedOptions = [];
                (this.entitiesList || []).forEach(function (entity) {
                    entityTranslatedOptions[entity] = this.translate(entity, 'scopeNames', 'Global');
                }, this);

                this.createView('entity', 'views/fields/enum', {
                    model: this.panelModel,
                    el: `${this.options.el} .field[data-name="entity"]`,
                    name: 'entity',
                    params: {
                        options: this.entitiesList || [],
                        translatedOptions: entityTranslatedOptions
                    },
                    inlineEditDisabled: true,
                    prohibitedEmptyValue: true,
                    mode: this.mode
                }, view => view.render());

                this.listenTo(this.panelModel, 'change:delimiter', () => {
                    if (!this.validateDelimiters()) {
                        this.configData.delimiter = this.panelModel.get('delimiter');
                    }
                });

                this.createView('delimiter', 'export:views/export-feed/fields/field-value-delimiter', {
                    model: this.panelModel,
                    el: `${this.options.el} .field[data-name="delimiter"]`,
                    name: 'delimiter',
                    inlineEditDisabled: true,
                    mode: this.mode
                }, view => view.render());

                this.createView('allFields', 'views/fields/bool', {
                    model: this.panelModel,
                    el: `${this.options.el} .field[data-name="allFields"]`,
                    name: 'allFields',
                    inlineEditDisabled: true,
                    mode: this.mode
                }, view => view.render());

                this.listenTo(this.panelModel, 'change:emptyValue', () => {
                    this.configData.emptyValue = this.panelModel.get('emptyValue');
                });

                this.createView('emptyValue', 'views/fields/varchar', {
                    model: this.panelModel,
                    el: `${this.options.el} .field[data-name="emptyValue"]`,
                    name: 'emptyValue',
                    inlineEditDisabled: true,
                    mode: this.mode
                }, view => view.render());

                this.listenTo(this.panelModel, 'change:nullValue', () => {
                    this.configData.nullValue = this.panelModel.get('nullValue');
                });

                this.createView('nullValue', 'views/fields/varchar', {
                    model: this.panelModel,
                    el: `${this.options.el} .field[data-name="nullValue"]`,
                    name: 'nullValue',
                    inlineEditDisabled: true,
                    mode: this.mode
                }, view => view.render());

                this.createView('markForNotLinkedAttribute', 'views/fields/varchar', {
                    model: this.panelModel,
                    el: `${this.options.el} .field[data-name="markForNotLinkedAttribute"]`,
                    name: 'markForNotLinkedAttribute',
                    inlineEditDisabled: true,
                    mode: this.mode
                }, view => {
                    view.render();

                    if (this.panelModel.get('entity') !== 'Product') {
                        view.hide();
                    } else {
                        view.show();
                    }

                    this.listenTo(this.panelModel, 'change:entity', () => {
                        if (this.panelModel.get('entity') !== 'Product') {
                            view.hide();
                        } else {
                            view.show();
                        }
                    });
                });
            });
        },

        loadAllFields() {
            if (!this.panelModel.get('allFields')) {
                return false;
            }

            this.ajaxGetRequest(`ExportFeed/action/GetAllFieldsConfigurator?scope=${this.panelModel.get('entity')}`, {}, {async: false}).then(configuration => {
                this.configData.configuration = configuration;
            });

            return true;
        },

        updatePanelModelAttributes() {
            this.panelModel.set({
                entity: (this.configData || {}).entity || null,
                delimiter: (this.configData || {}).delimiter || null,
                allFields: (this.configData || {}).allFields || null,
                emptyValue: (this.configData || {}).emptyValue || '',
                nullValue: (this.configData || {}).nullValue || 'Null',
                markForNotLinkedAttribute: (this.configData || {}).markForNotLinkedAttribute || '--',
            }, {silent: true});
        },

        createConfiguratorList() {
            this.clearView('configurator');
            this.getCollectionFactory().create('ExportFeed', collection => {
                this.collection = collection;
                this.updateCollection();

                this.listenTo(this.collection, 'updateColumn', () => {
                    this.configData = this.getConfigurationData();
                    if (this.mode !== 'edit') {
                        this.save(() => this.createConfiguratorList());
                    }
                });

                this.listenTo(this.collection, 'configuration-update', model => {
                    if (model) {
                        this.updateModelInCollection(model);
                    }
                    this.configData = this.getConfigurationData();
                    this.setupSelected();
                    this.save(() => this.createConfiguratorList());
                });

                this.listenTo(this.collection, 'configuration-sorted', ids => {
                    this.collection.models.sort((m1, m2) => ids.indexOf(m1.id) - ids.indexOf(m2.id));
                    this.configData = this.getConfigurationData();
                    if (this.mode !== 'edit') {
                        this.save(() => this.createConfiguratorList());
                    }
                });

                let mode = this.mode;
                let rowActionsView = this.configRowActionsView;
                let dragableListRows = true;

                if (this.panelModel.get('allFields')) {
                    mode = 'list';
                    rowActionsView = 'views/record/row-actions/empty';
                    dragableListRows = false;
                }

                this.createView('configurator', this.configListView, {
                    scope: this.model.name,
                    collection: collection,
                    el: `${this.options.el} .list-container`,
                    listLayout: this.getCollectionLayout(),
                    checkboxes: false,
                    showMore: false,
                    rowActionsView: rowActionsView,
                    buttonsDisabled: true,
                    dragableListRows: dragableListRows,
                    mode: mode === 'detail' ? 'list' : mode,
                    configFieldEditView: this.configFieldEditView,
                    translates: this.translates,
                    configAttributeEditView: this.configAttributeEditView,
                    entityFields: this.entityFields,
                    selectedFields: this.selectedFields
                }, view => {
                    this.listenToOnce(view, 'after:render', () => {
                        this.model.trigger('configuration-entity-changed', this.panelModel.get('entity'));
                    });

                    this.listenTo(view, 'after:render', () => {
                        const $buttonGroup = view.$el.parent().parent().find('.panel-heading .btn-group');

                        if (this.configData.allFields) {
                            $buttonGroup.hide();
                        } else {
                            $buttonGroup.show();
                        }
                    });

                    view.render();
                });
            });
        },

        updateModelInCollection(m) {
            let model;
            if (m.id) {
                model = this.collection.get(m.id);
                model.set(m.getClonedAttributes(), {silent: true});
            } else {
                model = m;
                m.id = Math.max(...this.collection.map(model => model.id)) + 1;
                this.collection.add(m);
                this.collection._byId[m.id] = m;
                this.collection.total++;
            }
        },

        updateCollection() {
            this.collection.reset();

            let configuration = (this.configData || {}).configuration || [];
            this.collection.total = configuration.length;
            configuration.forEach((item, i) => {
                this.getModelFactory().create(null, model => {
                    model.set(_.extend(item, {
                        entity: this.panelModel.get('entity'),
                        allFields: this.panelModel.get('allFields'),
                        emptyValue: this.panelModel.get('emptyValue'),
                        nullValue: this.panelModel.get('nullValue'),
                        markForNotLinkedAttribute: this.panelModel.get('markForNotLinkedAttribute')
                    }));
                    model.id = i + 1;
                    this.collection.add(model);
                    this.collection._byId[model.id] = model;
                });
            });
        },

        getCollectionLayout() {
            let listLayout = [];

            listLayout.push({
                widthPx: '40',
                align: 'center',
                notSortable: true,
                customLabel: '',
                params: {
                    allFields: this.panelModel.get('allFields'),
                },
                name: 'draggableIcon',
                view: 'export:views/fields/draggable-list-icon'
            });

            listLayout.push({
                name: 'field',
                notSortable: true,
                type: 'varchar',
                params: {
                    readOnly: true,
                    required: true,
                    inlineEditDisabled: true,
                },
                view: 'export:views/export-feed/fields/varchar-with-info'
            });

            listLayout.push({
                name: 'column',
                notSortable: true,
                type: 'varchar',
                params: {
                    required: true,
                    listView: true,
                    configurator: this.configData,
                    translates: this.translates,
                    inlineEditDisabled: !this.getAcl().check('ExportFeed', 'edit') || this.panelModel.get('allFields')
                },
                view: "export:views/export-feed/fields/column"
            });

            listLayout.push({
                name: 'remove',
                notSortable: true,
                params: {
                    readOnly: true,
                    inlineEditDisabled: true,
                    allFields: this.panelModel.get('allFields'),
                },
                view: "export:views/export-feed/fields/remove",
                width: '20'
            });

            return listLayout;
        },

        actionAddEntityField() {
            this.notify('Loading...');

            this.createView('modal', this.configFieldEditView, {
                scope: this.panelModel.get('entity'),
                configurator: this.configData,
                entityFields: this.entityFields,
                translates: this.translates,
                selectedFields: this.selectedFields,
                collection: this.collection,
                isAdd: true,
            }, view => {
                view.once('after:render', () => {
                    this.notify(false);
                });

                view.render();

                this.listenToOnce(view, 'remove', () => {
                    this.clearView('modal');
                });

                this.listenToOnce(view, 'after:save', m => this.collection.trigger('configuration-update', m));
            });
        },

        actionAddProductAttribute() {
            this.notify('Loading...');

            this.createView('modal', this.configAttributeEditView, {
                isAttribute: true,
                scope: 'Attribute',
                entityFields: this.entityFields,
                selectedFields: this.selectedFields,
                fileColumns: this.fileColumns,
                collection: this.collection,
                isAdd: true,
            }, view => {
                view.once('after:render', () => {
                    this.notify(false);
                });

                view.render();

                this.listenToOnce(view, 'remove', () => {
                    this.clearView('modal');
                });

                this.listenToOnce(view, 'after:save', m => this.collection.trigger('configuration-update', m));
            });
        },

        save(callback) {
            let self = this;
            let data = _.extend({}, this.model.get('data'), this.configData);
            this.model.set({data: data}, {silent: true});
            this.notify('Loading...');
            this.model.save({data: data, _silentMode: true}, {
                success: () => {
                    this.notify('Saved', 'success');
                    this.initialData = Espo.Utils.cloneDeep(this.configData);
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                },
                error: (e, xhr) => {
                    let statusReason = xhr.getResponseHeader('X-Status-Reason') || '';
                    if (xhr.status === 304) {
                        Espo.Ui.notify(self.translate('notModified', 'messages'), 'warning', 1000 * 60 * 60 * 2, true);
                    } else {
                        Espo.Ui.notify(`${self.translate("Error")} ${xhr.status}: ${statusReason}`, "error", 1000 * 60 * 60 * 2, true);
                    }
                },
                patch: !this.model.isNew()
            });
        },

        fetch() {
            return {
                data: _.extend({}, this.model.get('data'), this.configData)
            };
        },

        validate() {
            for (let i in this.validations) {
                let method = 'validate' + Espo.Utils.upperCaseFirst(this.validations[i]);
                if (this[method].call(this)) {
                    this.trigger('invalid');
                    return true;
                }
            }
            return false;
        },

        validateConfigurator() {
            let validate = false;
            let configurator = this.getView('configurator');
            if (configurator) {
                Object.keys(configurator.nestedViews).forEach(row => {
                    let rowView = configurator.nestedViews[row];
                    if (rowView) {
                        Object.keys(rowView.nestedViews).forEach(field => {
                            let fieldView = rowView.nestedViews[field];
                            if (fieldView && fieldView.mode === 'edit' && typeof fieldView.validate === 'function'
                                && !fieldView.disabled && !fieldView.readOnly) {
                                validate = fieldView.validate() || validate;
                            }
                        });
                    }
                });
            }
            return validate;
        },

        validateDelimiters() {
            let validate = false;
            let msg = '';

            let delimiter = this.panelModel.get('delimiter') || '';

            if (delimiter.indexOf('|') >= 0) {
                msg = this.translate('systemDelimiter', 'messages', 'ExportFeed');
                validate = true;
            }

            if (!validate && this.model.get('fileType') === 'csv') {
                let csvDelimiter = this.model.get('csvFieldDelimiter') || '';
                for (let i = 0; i < delimiter.length; i++) {
                    if (csvDelimiter.indexOf(delimiter.charAt(i)) >= 0) {
                        msg = this.translate('delimitersMustBeDifferent', 'messages', 'ExportFeed');
                        validate = true;
                    }
                }
            }

            if (validate) {
                let delimiter = this.getView('delimiter');
                delimiter.trigger('invalid');
                delimiter.showValidationMessage(msg);
            }

            return validate;
        },

        getConfigurationData() {
            let data = {
                entity: this.panelModel.get('entity'),
                allFields: this.panelModel.get('allFields'),
                emptyValue: this.panelModel.get('emptyValue'),
                nullValue: this.panelModel.get('nullValue'),
                markForNotLinkedAttribute: this.panelModel.get('markForNotLinkedAttribute'),
                delimiter: this.panelModel.get('delimiter')
            };
            data.configuration = this.collection.map(model => model.getClonedAttributes());
            return data;
        },

        setDetailMode() {
            this.mode = 'detail';
            this.configuratorFields.forEach(field => {
                let view = this.getView(field);
                if (view) {
                    view.setMode('detail');
                    view.reRender();
                }
            });
            let configurator = this.getView('configurator');
            if (configurator) {
                configurator.setListMode();
            }
        },

        setEditMode() {
            this.mode = 'edit';
            this.configuratorFields.forEach(field => {
                let view = this.getView(field);
                if (view) {
                    view.setMode('edit');
                    view.reRender();
                }
            });
            let configurator = this.getView('configurator');
            if (configurator && !this.configData.allFields) {
                configurator.setEditMode();
            }
        },

        cancelEdit() {
            this.configData = Espo.Utils.cloneDeep(this.initialData);
            this.entityFields = this.getEntityFields(this.configData.entity);
            this.setupSelected();
            this.updatePanelModelAttributes();
            this.createConfiguratorList();
            this.reRender();
        }

    })
);
