

Espo.define('export:views/export-feed/simple-type-components/record/simple-type-list', 'views/record/list',
    Dep => Dep.extend({

        template: 'export:export-feed/simple-type-components/record/simple-type-list',

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.collection, 'actionRemove', model => this.actionQuickRemove({id: model.id}));
        },

        saveListItemOrder() {
            let ids = [];
            this.$el.find(`${this.listContainerEl} tr`).each((i, el) => ids.push($(el).data('id')));
            this.collection.trigger('configuration-sorted', ids);
        },

        prepareInternalLayout(internalLayout, model) {
            Dep.prototype.prepareInternalLayout.call(this, internalLayout, model);

            internalLayout.forEach(item => {
                item.options.mode = !item.options.defs.params.readOnly ? this.options.mode : item.options.mode;
            });
        },

        setListMode() {
            this.mode = 'list';
            this.updateModeInFields(this.mode);
        },

        setEditMode() {
            this.mode = 'edit';
            this.updateModeInFields(this.mode);
        },

        updateModeInFields(mode) {
            Object.keys(this.nestedViews).forEach(row => {
                let rowView = this.nestedViews[row];
                if (rowView) {
                    Object.keys(rowView.nestedViews).forEach(field => {
                        let fieldView = rowView.nestedViews[field];
                        if (fieldView && typeof fieldView.setMode === 'function' && !fieldView.readOnly && !fieldView.disabled) {
                            fieldView.setMode(mode);
                            fieldView.reRender();
                        }
                    });
                }
            });
        },

        actionQuickEdit(data) {
            data = data || {};
            let id = data.id;
            if (!id) return;

            let model = this.collection.get(id);
            if (model && this.scope) {
                this.notify(this.translate('loading', 'messages'));

                let view = model.get('attributeId') ? this.options.configAttributeEditView : this.options.configFieldEditView;
                this.createView('modal', view, {
                    scope: model.get('entity'),
                    id: data.id,
                    model: model,
                    entityFields: this.options.entityFields,
                    selectedFields: this.options.selectedFields
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
            }
        },

        actionQuickRemove(data) {
            data = data || {};
            let id = data.id;
            if (!id) return;

            this.confirm({
                message: this.translate('removeRecordConfirmation', 'messages'),
                confirmText: this.translate('Remove')
            }, () => {
                this.removeRecordFromList(id);
                this.collection.trigger('configuration-update')
            });
        }

    })
);