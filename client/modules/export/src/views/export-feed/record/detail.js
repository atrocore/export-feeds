

Espo.define('export:views/export-feed/record/detail', 'export:views/record/detail',
    Dep => Dep.extend({

        template: 'export:export-feed/record/detail',

        setup() {
            Dep.prototype.setup.call(this);

            this.hideField('channel');
            this.listenTo(this.model, 'configuration-entity-changed', condition => {
                if (condition) {
                    this.showPanel('simpleTypeProductFilter');
                    this.showField('channel');
                } else {
                    this.hidePanel('simpleTypeProductFilter');
                    this.hideField('channel');
                }
            });

            this.listenTo(this.model, 'after:save', () => {
                this.handleExportButtonVisibility();
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.handleExportButtonVisibility();
        },

        handleExportButtonVisibility() {
            const exportButton = this.$el.find('button[data-action="exportNow"]');

            this.model.get('isActive') ? exportButton.show() : exportButton.hide();
        },

        actionExportNow() {
            const exportButton = this.$el.find('button[data-action="exportNow"]');
            exportButton.prop('disabled', true);
            this.ajaxPostRequest(`ExportFeed/${this.model.id}/exportByFeed`).then(response => {
                this.notify(this.translate(response ? 'jobCreated' : 'jobNotCreated', 'additionalTranslates', 'ExportFeed'), response ? 'success' : 'danger');
                Backbone.trigger('showQueuePanel');
            }).always(() => {
                exportButton.prop('disabled', false);
            });
        },

        getBottomPanels() {
            let bottomView = this.getView('bottom');
            if (bottomView) {
                return bottomView.nestedViews;
            }
            return null;
        },

        setDetailMode() {
            let panels = this.getBottomPanels();
            if (panels) {
                for (let panel in panels) {
                    if (typeof panels[panel].setDetailMode === 'function') {
                        panels[panel].setDetailMode();
                    }
                }
            }
            Dep.prototype.setDetailMode.call(this);
        },

        setEditMode() {
            Dep.prototype.setEditMode.call(this);

            let panels = this.getBottomPanels();
            if (panels) {
                for (let panel in panels) {
                    if (typeof panels[panel].setEditMode === 'function') {
                        panels[panel].setEditMode();
                    }
                }
            }
        },

        cancelEdit() {
            Dep.prototype.cancelEdit.call(this);

            let panels = this.getBottomPanels();
            if (panels) {
                for (let panel in panels) {
                    if (typeof panels[panel].cancelEdit === 'function') {
                        panels[panel].cancelEdit();
                    }
                }
            }
        },


        save: function (callback, skipExit) {
            this.beforeBeforeSave();

            var data = this.fetch();

            var self = this;
            var model = this.model;

            var initialAttributes = this.attributes;

            var beforeSaveAttributes = this.model.getClonedAttributes();

            data = _.extend(Espo.Utils.cloneDeep(beforeSaveAttributes), data);

            var attrs = false;
            if (model.isNew()) {
                attrs = data;
            } else {
                for (var name in data) {
                    if (typeof initialAttributes[name] === 'undefined' || _.isEqual(initialAttributes[name], data[name])) {
                        continue;
                    }
                    (attrs || (attrs = {}))[name] = data[name];
                }
            }

            if (this.validate()) {
                model.attributes = beforeSaveAttributes;
                this.trigger('cancel:save');
                this.afterNotValid();
                return;
            }

            if (this.validatePanels()) {
                this.trigger('cancel:save');
                this.enableButtons();
                return;
            }

            let changesFromPanels = this.handlePanelsFetch();

            if (!attrs && !changesFromPanels) {
                this.trigger('cancel:save');
                this.afterNotModified();
                return true;
            }

            if (!attrs) {
                attrs = {};
            }

            attrs =  _.extend(attrs, changesFromPanels);

            model.set(attrs, {silent: true});

            this.beforeSave();

            this.trigger('before:save');
            model.trigger('before:save');

            model.save(attrs, {
                success: function () {
                    this.afterSave();
                    if (self.isNew) {
                        self.isNew = false;
                    }
                    this.trigger('after:save');
                    model.trigger('after:save');

                    if (!callback) {
                        if (!skipExit) {
                            if (self.isNew) {
                                this.exit('create');
                            } else {
                                this.exit('save');
                            }
                        }
                    } else {
                        callback(this);
                    }
                }.bind(this),
                error: function (e, xhr) {
                    var r = xhr.getAllResponseHeaders();
                    var response = null;

                    if (~[409, 500].indexOf(xhr.status)) {
                        var statusReasonHeader = xhr.getResponseHeader('X-Status-Reason');
                        if (statusReasonHeader) {
                            try {
                                var response = JSON.parse(statusReasonHeader);
                            } catch (e) {
                                console.error('Could not parse X-Status-Reason header');
                            }
                        }
                    }

                    if (response && response.reason) {
                        var methodName = 'errorHandler' + Espo.Utils.upperCaseFirst(response.reason.toString());
                        if (methodName in this) {
                            xhr.errorIsHandled = true;
                            this[methodName](response.data);
                        }
                    }

                    this.afterSaveError();

                    model.attributes = beforeSaveAttributes;
                    self.trigger('cancel:save');

                }.bind(this),
                patch: !model.isNew()
            });
            return true;
        },

        handlePanelsFetch() {
            let changes = false;
            let panels = this.getBottomPanels();
            if (panels) {
                for (let panel in panels) {
                    if (typeof panels[panel].fetch === 'function') {
                        changes = panels[panel].fetch() || changes;
                    }
                }
            }
            return changes;
        },

        validatePanels() {
            let notValid = false;
            let panels = this.getBottomPanels();
            if (panels) {
                for (let panel in panels) {
                    if (typeof panels[panel].validate === 'function') {
                        notValid = panels[panel].validate() || notValid;
                    }
                }
            }
            return notValid
        }

    })
);