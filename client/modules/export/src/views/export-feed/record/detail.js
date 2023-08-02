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

Espo.define('export:views/export-feed/record/detail', 'views/record/detail',
    Dep => Dep.extend({

        bottomView: 'export:views/export-feed/record/detail-bottom',

        setup() {
            Dep.prototype.setup.call(this);

            this.additionalButtons = [
                {
                    "action": "exportNow",
                    "label": this.translate('Export', 'labels', 'ExportFeed')
                }
            ];

            // Check if import module is installed
            if (this.getMetadata('entityDefs.ImportFeed') && this.model.get('type') === 'simple') {
                this.additionalButtons.push({
                    "action": "duplicateAsImport",
                    "label": this.translate('DuplicateAsImport', 'labels', 'ExportFeed')
                })
            }

            this.listenTo(this.model, 'after:save', () => {
                this.handleExportButtonDisability();
            });

            this.getStorage().set('mode', 'ExportFeed', null);
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.handleExportButtonDisability();
        },

        handleExportButtonDisability() {
            const $buttons = $('.additional-button[data-action="exportNow"]');
            if (this.hasExportNow()) {
                $buttons.removeClass('disabled');
            } else {
                $buttons.addClass('disabled');
            }
        },

        hasExportNow() {
            return this.model.get('isActive');
        },

        actionExportNow() {
            if (!this.hasExportNow()) {
                return;
            }

            this.ajaxPostRequest('ExportFeed/action/exportFile', {id: this.model.id}).then(response => {
                this.notify(this.translate(response ? 'jobCreated' : 'jobNotCreated', 'additionalTranslates', 'ExportFeed'), response ? 'success' : 'danger');
                $('.action[data-action="refresh"][data-panel="exportJobs"]').click();
            });
        },

        actionDuplicateAsImport() {
            this.confirm(this.translate('duplicateAsImport', 'messages', 'ExportFeed'), () => {
                const data = {
                    exportFeedId: this.model.get('id')
                };
                this.notify(this.translate('duplicate', 'labels', 'ImportFeed'));
                this.ajaxPostRequest('ImportFeed/action/createFromExport', data).then(response => {
                    if (response) {
                        this.notify('Created', 'success');
                        this.getRouter().navigate('#ImportFeed/view/' + response.id, {trigger: false});
                        this.getRouter().dispatch('ImportFeed', 'view', {
                            id: response.id,
                        })
                    }
                });
            });
        },

        setDetailMode() {
            Dep.prototype.setDetailMode.call(this);

            this.getStorage().set('mode', 'ExportFeed', 'detail');
            this.model.trigger('change:export-feed-mode');
        },

        setEditMode() {
            Dep.prototype.setEditMode.call(this);

            this.getStorage().set('mode', 'ExportFeed', 'edit');
            this.model.trigger('change:export-feed-mode');
        },

        save(callback, skipExit) {
            this.model.trigger('save:export-feed');

            Dep.prototype.save.call(this, callback, skipExit);
        },
    })
);