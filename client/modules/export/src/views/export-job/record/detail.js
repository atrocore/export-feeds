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

Espo.define('export:views/export-job/record/detail', 'views/record/detail', function (Dep) {

    return Dep.extend({

        duplicateAction: false,

        setupActionItems: function () {
            if (['Failed', 'Canceled'].includes(this.model.get('state'))) {
                this.dropdownItemList.push({
                    'name': 'tryAgainExportJob',
                    action: 'tryAgainExportJob',
                    label: 'tryAgain',
                });
            }
            Dep.prototype.setupActionItems.call(this);
        },
        actionTryAgainExportJob(data) {
            this.notify('Saving...');
            this.model.set('state', 'Pending');
            this.model.save().then(() => {
                this.notify('Saved', 'success');
            });
        },
        actionCancelExportJob(data) {
            this.notify('Saving...');
            this.model.set('state', 'Canceled');
            this.model.save().then(() => {
                this.notify('Saved', 'success');
            });
        },
        actionRemoveRelated: function (data) {
            let id = data.id;

            let message = 'Global.messages.removeRecordConfirmation';
            if (this.getMetadata().get(`scopes.${this.scope}.type`) === 'Hierarchy') {
                message = 'Global.messages.removeRecordConfirmationHierarchically';
            }

            let scopeMessage = this.getMetadata().get(`clientDefs.${this.scope}.deleteConfirmation`);
            if (scopeMessage) {
                message = scopeMessage;
            }

            let parts = message.split('.');

            this.confirm({
                message: this.translate(parts.pop(), parts.pop(), parts.pop()),
                confirmText: this.translate('Remove')
            }, () => {
                let model = this.collection.get(id);
                this.notify('removing');
                model.destroy({
                    success: () => {
                        this.notify('Removed', 'success');
                        this.collection.fetch();
                        this.model.trigger('after:unrelate', this.link, this.defs);
                    },

                    error: () => {
                        this.collection.push(model);
                    }
                });
            });
        },
    });

});