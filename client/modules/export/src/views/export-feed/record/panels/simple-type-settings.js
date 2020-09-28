

Espo.define('export:views/export-feed/record/panels/simple-type-settings', 'views/record/panels/bottom',
    Dep => Dep.extend({

        template: 'export:export-feed/record/panels/simple-type-settings',

        configListView: 'export:views/export-feed/simple-type-components/record/simple-type-list',

        configRowActionsView: 'export:views/export-feed/simple-type-components/record/panels/row-actions/custom-edit',

        configFieldEditView: 'export:views/export-feed/simple-type-components/modals/field-edit',

        configAttributeEditView: 'export:views/export-feed/simple-type-components/modals/attribute-edit',

        configuratorFields: ['entity', 'delimiter'],

        validations: ['configurator', 'delimiters'],

        initialData: null,

        configData: null,

        defaultEntity: 'Product',

        defaultDelimiter: ',',

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

        loadConfiguration(entity) {
            this.entitiesList = this.getEntitiesList();
            let data = this.model.get('data');

            if (!entity && Espo.Utils.isObject(data)) {
                this.entityFields = this.getEntityFields(data.entity);
            } else {
                data = {};
                data.delimiter = this.defaultDelimiter;
                data.entity = entity || (this.entitiesList.includes(this.defaultEntity) ? this.defaultEntity : this.entitiesList[0]);
                this.entityFields = this.getEntityFields(data.entity);
                data.configuration = this.getEntityConfiguration(data.entity);
            }
            this.configData = data;
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
                    if (!notExportedType.includes(field.type) && (name === 'code' || !field.emHidden) && !field.disabled
                        && !field.exportDisabled && !field.customizationDisabled && !field.notStorable) {
                        result[name] = fields[name];
                    }
                });
            }
            return result;
        },

        getEntityConfiguration(entity) {
            let result = [{
                field: 'id',
                column: this.translate('id', 'fields', 'Global')
            }];
            Object.keys(this.entityFields).forEach(name => {
                let field = this.entityFields[name];
                if (field.required) {
                    let data = {
                        field: name,
                        column: this.translate(name, 'fields', entity)
                    };
                    if (['link', 'linkMultiple'].includes(field.type)) {
                        _.extend(data, {
                            exportBy: 'id'
                        });
                    }
                    result.push(data);
                }
            });
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

                this.listenTo(this.panelModel, 'change:entity', () => {
                    this.loadConfiguration(this.panelModel.get('entity'));
                    this.updatePanelModelAttributes();
                    this.createConfiguratorList();
                    this.reRender();
                    this.model.trigger('configuration-entity-changed', this.panelModel.get('entity') === 'Product');
                });

                this.createView('entity', 'views/fields/enum', {
                    model: this.panelModel,
                    el: `${this.options.el} .field[data-name="entity"]`,
                    name: 'entity',
                    params: {
                        options: this.entitiesList,
                        translatedOptions: this.entitiesList.reduce((prev, curr) => prev[curr] = this.translate(curr, 'scopeNames'), {})
                    },
                    inlineEditDisabled: true,
                    mode: this.mode
                }, view => view.render());

                this.createView('delimiter', 'export:views/export-feed/fields/field-value-delimiter', {
                    model: this.panelModel,
                    el: `${this.options.el} .field[data-name="delimiter"]`,
                    name: 'delimiter',
                    inlineEditDisabled: true,
                    mode: this.mode
                }, view => view.render());
            });
        },

        updatePanelModelAttributes() {
            this.panelModel.set({
                entity: (this.configData || {}).entity || null,
                delimiter: (this.configData || {}).delimiter || null
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
                    if (this.mode !== 'edit') {
                        this.save(() => this.createConfiguratorList());
                    } else {
                        this.createConfiguratorList();
                    }
                });

                this.listenTo(this.collection, 'configuration-sorted', ids => {
                    this.collection.models.sort((m1, m2) => ids.indexOf(m1.id) - ids.indexOf(m2.id));
                    this.configData = this.getConfigurationData();
                    if (this.mode !== 'edit') {
                        this.save(() => this.createConfiguratorList());
                    }
                });

                this.createView('configurator', this.configListView, {
                    scope: this.model.name,
                    collection: collection,
                    el: `${this.options.el} .list-container`,
                    listLayout: this.getCollectionLayout(),
                    checkboxes: false,
                    showMore: false,
                    rowActionsView: this.configRowActionsView,
                    buttonsDisabled: true,
                    dragableListRows: true,
                    mode: this.mode === 'detail' ? 'list' : this.mode,
                    configFieldEditView: this.configFieldEditView,
                    configAttributeEditView: this.configAttributeEditView,
                    entityFields: this.entityFields,
                    selectedFields: this.selectedFields,
                }, view => {
                    this.listenToOnce(view, 'after:render', () => {
                        this.model.trigger('configuration-entity-changed', this.panelModel.get('entity') === 'Product');
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
                    model.set(_.extend(item, {entity: this.panelModel.get('entity')}));
                    model.id = i + 1;
                    this.collection.add(model);
                    this.collection._byId[model.id] = model;
                });
            });
        },

        getCollectionLayout() {
            let listLayout = [
                {
                    widthPx: '40',
                    align: 'center',
                    notSortable: true,
                    customLabel: '',
                    name: 'draggableIcon',
                    view: 'treo-core:views/fields/draggable-list-icon'
                },
                {
                    name: 'field',
                    notSortable: true,
                    type: 'varchar',
                    params: {
                        readOnly: true,
                        required: true,
                        inlineEditDisabled: true
                    },
                    view: 'export:views/export-feed/fields/varchar-with-info'
                },
                {
                    name: 'column',
                    notSortable: true,
                    type: 'varchar',
                    params: {
                        required: true,
                        inlineEditDisabled: !this.getAcl().check('ExportFeed', 'edit')
                    },
                    view: "export:views/export-feed/fields/column"
                }
            ];

            if (this.panelModel.get('entity') === 'Product') {
                listLayout.push({
                    name: 'channel',
                    customLabel: this.translate('Channel', 'scopeNames', 'Global'),
                    notSortable: true,
                    type: 'link',
                    params: {
                        readOnly: true,
                        required: true
                    },
                    view: 'export:views/export-feed/fields/channel'
                });
            }

            listLayout.push({
                name: 'remove',
                notSortable: true,
                params: {
                    readOnly: true,
                    inlineEditDisabled: true
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
                entityFields: this.entityFields,
                selectedFields: this.selectedFields
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
                fileColumns: this.fileColumns
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
            let data = _.extend({}, this.model.get('data'), this.configData);
            this.model.set({data: data}, {silent: true});
            this.notify('Loading...');
            this.model.save({data: data}, {
                success: () => {
                    this.notify('Saved', 'success');
                    this.initialData = Espo.Utils.cloneDeep(this.configData);
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                },
                error: () => {
                    this.cancelEdit();
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
            if (this.model.get('csvFieldDelimiter') === this.panelModel.get('delimiter')) {
                let delimiter = this.getView('delimiter');
                delimiter.trigger('invalid');
                let msg = this.translate('delimitersMustBeDifferent', 'messages', 'ExportFeed');
                delimiter.showValidationMessage(msg);
                validate = true;
            }
            return validate;
        },

        getConfigurationData() {
            let data = {
                entity: this.panelModel.get('entity'),
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
            if (configurator) {
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
