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

Espo.define('export:views/export-feed/record/detail', 'export:views/record/detail',
    Dep => Dep.extend({

        template: 'export:export-feed/record/detail',

        setup() {
            Dep.prototype.setup.call(this);

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

            if (this.model.get('isActive')) {
                exportButton.attr('disabled', false);
            } else {
                exportButton.attr('disabled', true);
            }
        },

        actionExportNow() {
            const exportButton = this.$el.find('button[data-action="exportNow"]');
            exportButton.prop('disabled', true);
            this.ajaxPostRequest('ExportFeed/action/exportFile', {id: this.model.id}).then(response => {
                this.notify(this.translate(response ? 'jobCreated' : 'jobNotCreated', 'additionalTranslates', 'ExportFeed'), response ? 'success' : 'danger');
                $('.action[data-action="refresh"][data-panel="exportResults"]').click();
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

            this.model.trigger('after:set-feed-mode', 'detail');
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

            this.model.trigger('after:set-feed-mode', 'edit');
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

            this.beforeSave();

            this.trigger('before:save', attrs);
            model.trigger('before:save', attrs);

            model.set(attrs, {silent: true});

            attrs['_silentMode'] = true;

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
                    let statusReason = xhr.getResponseHeader('X-Status-Reason') || '';

                    if (xhr.status === 304) {
                        Espo.Ui.notify(self.translate('notModified', 'messages'), 'warning', 1000 * 60 * 60 * 2, true);
                    } else {
                        Espo.Ui.notify(`${self.translate("Error")} ${xhr.status}: ${statusReason}`, "error", 1000 * 60 * 60 * 2, true);
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