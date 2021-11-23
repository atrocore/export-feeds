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

                    this.notify('Removing...');
                    this.model.destroy({
                        wait: true,
                        success: function () {
                            this.notify('Removed', 'success');
                            $('.action[data-action=refresh][data-panel=configuratorItems]').click();
                        }.bind(this)
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

            if (this.model.get('allFields')) {
                this.buttonDisabled = true;
            }

            this.listenTo(this.model.collection, 'model-removing', () => {
                this.buttonDisabled = true;
                this.$el.find('button').prop('disabled', true);
            });

            this.listenTo(this.model.collection, 'after:model-removing', () => {
                this.buttonDisabled = false;
                this.$el.find('button').prop('disabled', false);
            });
        }
    })
});