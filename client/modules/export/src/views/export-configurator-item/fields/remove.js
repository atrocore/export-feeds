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

Espo.define('export:views/export-configurator-item/fields/remove', 'view', function (Dep) {

    return Dep.extend({

        template: 'export:export-configurator-item/fields/remove/list',

        buttonDisabled: false,

        events: {
            'click button[data-action="actionRemove"]': function () {
                if (!this.buttonDisabled) {
                    if (!this.getAcl().checkModel(this.model, 'delete')) {
                        this.notify('Access denied', 'error');
                        return false;
                    }

                    this.buttonDisabled = true;

                    this.notify('Removing...');

                    this.ajaxRequest(`ExportConfiguratorItem/${this.model.get('id')}?skip404=1`, 'DELETE').then(response => {
                        this.notify('Removed', 'success');
                        $('.action[data-action=refresh][data-panel=configuratorItems]').click();
                    });
                }
            }
        },

        data() {
            return {
                disabled: this.buttonDisabled
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.buttonDisabled = !this.getAcl().check('ExportFeed', 'edit');
        }
    })
});