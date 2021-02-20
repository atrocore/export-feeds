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

Espo.define('export:views/export-feed/fields/remove', 'view', function (Dep) {

    return Dep.extend({

        template: 'export:export-feed/fields/remove/list',

        buttonDisabled: false,

        events: {
            'click button[data-action="actionRemove"]': function () {
                if (!this.buttonDisabled) {
                    this.model.collection.trigger('actionRemove', this.model);
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