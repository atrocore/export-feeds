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

Espo.define('export:views/export-feed/record/panels/configurator-items', 'views/record/panels/relationship',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            if (this.getAcl().check('ExportFeed', 'edit')) {
                this.actionList = [
                    {
                        label: 'addMissingFields',
                        action: 'addMissingFields'
                    },
                    {
                        label: 'selectAttributes',
                        action: 'selectAttributes'
                    },
                    {
                        label: 'removeAllItems',
                        action: 'removeAllItems'
                    }
                ];
            }

            this.listenTo(this.model, 'change:entity', () => {
                this.prepareActionsVisibility();
            });

            this.listenTo(this.model, 'change:fileType', () => {
                this.reRender();
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.prepareActionsVisibility();

            this.$el.parent().hide();
            if (['csv', 'xlsx'].includes(this.model.get('fileType'))) {
                this.$el.parent().show();
            }
        },

        prepareActionsVisibility() {
            const $selectAttributes = $('.action[data-action=selectAttributes][data-panel=configuratorItems]');

            if (this.model.get('entity') === 'Product') {
                $selectAttributes.show();
            } else {
                $selectAttributes.hide();
            }
        },

        actionAddMissingFields() {
            this.confirm(this.translate('addMissingFieldsConfirmation', 'labels', 'ExportFeed'), () => {
                this.notify('Saving...');

                let postData = {
                    exportFeedId: this.model.get('id')
                };

                this.ajaxPostRequest(`ExportFeed/action/addMissingFields`, postData).then(response => {
                    this.notify('Saved', 'success');
                    $('.action[data-action=refresh][data-panel=configuratorItems]').click();
                });
            });
        },

        actionRemoveAllItems() {
            this.confirm(this.translate('removeAllItemsConfirmation', 'labels', 'ExportFeed'), () => {
                this.notify('Removing...');

                let postData = {
                    exportFeedId: this.model.get('id')
                };

                this.ajaxPostRequest(`ExportFeed/action/removeAllItems`, postData).then(response => {
                    this.notify('Removed', 'success');
                    $('.action[data-action=refresh][data-panel=configuratorItems]').click();
                });
            });
        },

        actionSelectAttributes() {
            const scope = 'Attribute';
            const viewName = this.getMetadata().get(['clientDefs', scope, 'modalViews', 'select']) || 'views/modals/select-records';

            this.notify('Loading...');
            this.createView('dialog', viewName, {
                scope: scope,
                multiple: true,
                createButton: false,
                massRelateEnabled: true
            }, dialog => {
                dialog.render();
                this.notify(false);
                dialog.once('select', selectObj => {
                    this.notify('Saving...');

                    let postData = {
                        exportFeedId: this.model.get('id')
                    };
                    if (!selectObj.massRelate) {
                        postData.ids = [];
                        selectObj.forEach(model => {
                            postData.ids.push(model.get('id'));
                        });
                    } else {
                        postData.where = selectObj.where;
                    }

                    this.ajaxPostRequest(`ExportFeed/action/addAttributes`, postData).then(response => {
                        this.notify('Saved', 'success');
                        $('.action[data-action=refresh][data-panel=configuratorItems]').click();
                    });
                });
            });
        },

    })
);