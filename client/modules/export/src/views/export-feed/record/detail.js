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
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

Espo.define('export:views/export-feed/record/detail', 'views/record/detail',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.additionalButtons = [
                {
                    "action": "exportNow",
                    "label": this.translate('Export', 'labels', 'ExportFeed')
                }
            ];

            this.listenTo(this.model, 'after:save', () => {
                this.handleExportButtonDisability();
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.handleExportButtonDisability();
        },

        handleExportButtonDisability() {
            const $buttons = $('.additional-button');
            if (this.model.get('isActive')) {
                $buttons.removeClass('disabled');
            } else {
                $buttons.addClass('disabled');
            }
        },

        actionExportNow() {
            if (this.validateConfigurator()) {
                this.notify(this.translate('noConfiguratorItems', 'exceptions', 'ExportFeed'), 'error');
                return;
            }

            this.ajaxPostRequest('ExportFeed/action/exportFile', {id: this.model.id}).then(response => {
                this.notify(this.translate(response ? 'jobCreated' : 'jobNotCreated', 'additionalTranslates', 'ExportFeed'), response ? 'success' : 'danger');
                $('.action[data-action="refresh"][data-panel="exportJobs"]').click();
            });
        },

        setDetailMode() {
            Dep.prototype.setDetailMode.call(this);

            this.model.trigger('change:export-feed-mode');
        },

        setEditMode() {
            Dep.prototype.setEditMode.call(this);

            this.model.trigger('change:export-feed-mode');
        },

        save(callback, skipExit) {
            this.model.trigger('save:export-feed');

            Dep.prototype.save.call(this, callback, skipExit);
        },

        validateConfigurator() {
            if (['csv', 'xlsx'].includes(this.model.get('fileType'))) {
                const configuratorItemsView = this.getView('bottom').getView('configuratorItems');
                if (configuratorItemsView) {
                    const collection = configuratorItemsView.collection;

                    if (collection && collection.length === 0) {
                        return true;
                    }
                }
            }

            return false;
        }
    })
);