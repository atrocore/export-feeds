/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.md, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
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

            this.$el.parent().show();

            let fileType = this.model.get('fileType');
            if (fileType) {
                if (!['csv', 'xlsx'].includes(fileType)) {
                    this.$el.parent().hide();
                }
            }
            if (this.model.get('hasMultipleSheets')) {
                this.$el.parent().hide();
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
                    entityType: this.model.urlRoot,
                    id: this.model.get('id')
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
                    entityType: this.model.urlRoot,
                    id: this.model.get('id')
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
                        entityType: this.model.urlRoot,
                        id: this.model.get('id')
                    };
                    if (!selectObj.massRelate) {
                        postData.ids = [];
                        selectObj.forEach(model => {
                            postData.ids.push(model.id);
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