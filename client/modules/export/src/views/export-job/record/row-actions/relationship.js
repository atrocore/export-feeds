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

Espo.define('export:views/export-job/record/row-actions/relationship', 'views/record/row-actions/relationship', Dep => {

    return Dep.extend({

        getActionList() {
            let list = [];

            if (['Pending', 'Running'].includes(this.model.get('state')) && this.options.acl.edit) {
                list.push({
                    action: 'cancelExportJob',
                    label: 'Cancel',
                    data: {
                        id: this.model.id
                    }
                });
            }

            if (['Failed', 'Canceled'].includes(this.model.get('state')) && this.options.acl.edit) {
                list.push({
                    action: 'tryAgainExportJob',
                    label: 'tryAgain',
                    data: {
                        id: this.model.id
                    }
                });
            }

            if (this.options.acl.delete) {
                list.push({
                    action: 'removeRelated',
                    label: 'Remove',
                    data: {
                        id: this.model.id
                    }
                });
            }

            return list;
        }

    });

});