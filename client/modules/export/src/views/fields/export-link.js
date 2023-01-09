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

Espo.define('export:views/fields/export-link', 'views/fields/varchar',
    Dep => Dep.extend({
        listTemplate: 'export:fields/export-link/detail',

        detailTemplate: 'export:fields/export-link/detail',

        editTemplate: 'export:fields/export-link/detail',

        events: {
            'click .action[data-action="setUrl"]': function () {
                this.actionSetLink();
            }
        },

        actionSetLink() {
            let url = this.model.getFieldParam(this.name, 'dataUrl');
            if (url) {
                this.ajaxGetRequest(url).then(function (response) {
                    if (response.link) {
                        this.model.set({[this.name]: response.link});
                    }
                }.bind(this));
            }
        }
    })
);
