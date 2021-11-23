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

Espo.define('export:views/export-feed/record/panels/configurator-items', 'views/record/panels/relationship',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.actionList.push({
                label: 'addMissingFields',
                action: 'addMissingFields'
            });

            this.actionList.push({
                label: 'selectAttributes',
                action: 'selectAttributes'
            });

            this.actionList.push({
                label: 'removeAllItems',
                action: 'removeAllItems'
            });
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
            alert('2');
            // const scope = 'AttributeGroup';
            // const viewName = this.getMetadata().get(['clientDefs', scope, 'modalViews', 'select']) || 'views/modals/select-records';
            //
            // this.notify('Loading...');
            // this.createView('dialog', viewName, {
            //     scope: scope,
            //     multiple: true,
            //     createButton: false,
            //     massRelateEnabled: false,
            //     boolFilterList: ['withNotLinkedAttributesToProduct', 'fromAttributesTab'],
            //     boolFilterData: {withNotLinkedAttributesToProduct: this.model.id, fromAttributesTab: {tabId: this.defs.tabId}},
            //     whereAdditional: [
            //         {
            //             type: 'isLinked',
            //             attribute: 'attributes'
            //         }
            //     ]
            // }, dialog => {
            //     dialog.render();
            //     this.notify(false);
            //     dialog.once('select', selectObj => {
            //         this.notify('Loading...');
            //         if (!Array.isArray(selectObj)) {
            //             return;
            //         }
            //         let boolFilterList = this.getSelectBoolFilterList() || [];
            //         this.getFullEntityList('Attribute', {
            //             where: [
            //                 {
            //                     type: 'bool',
            //                     value: boolFilterList,
            //                     data: this.getSelectBoolFilterData(boolFilterList)
            //                 },
            //                 {
            //                     attribute: 'attributeGroupId',
            //                     type: 'in',
            //                     value: selectObj.map(model => model.id)
            //                 }
            //             ]
            //         }, list => {
            //             let models = [];
            //             list.forEach(attributes => {
            //                 this.getModelFactory().create('Attribute', model => {
            //                     model.set(attributes);
            //                     models.push(model);
            //                 });
            //             });
            //             this.createProductAttributeValue(models);
            //         });
            //     });
            // });
        },

    })
);