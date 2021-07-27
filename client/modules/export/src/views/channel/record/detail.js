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

Espo.define('export:views/channel/record/detail', 'pim:views/channel/record/detail',
    Dep => Dep.extend({

        template: 'export:channel/record/detail',

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.handleExportButtonVisibility();
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'after:save', () => {
                this.handleExportButtonVisibility();
            });
        },

        handleExportButtonVisibility() {
            const button = this.$el.find('button[data-action="exportByChannel"]');
            this.model.get('isActive') ? button.removeClass('hidden') : button.addClass('hidden');
        },

        actionExportByChannel() {
            const button = this.$el.find('button[data-action="exportByChannel"]');
            button.prop('disabled', true);
            this.notify('Please wait');
            this.ajaxPostRequest('ExportFeed/action/exportChannel', {id: this.model.id}).then(response => {
                this.notify(this.translate(response ? 'jobCreated' : 'channelWithoutExportFeeds', 'additionalTranslates', 'ExportFeed'), response ? 'success' : 'danger');
                $('.action[data-action="refresh"][data-panel="exportResults"]').click();
            }).always(() => {
                button.prop('disabled', false);
            });
        }
    })
);

