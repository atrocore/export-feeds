

/*
 * This file is part of premium software, which is NOT free.
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This Software is the property of AtroCore UG (haftungsbeschränkt) and is
 * protected by copyright law - it is NOT Freeware and can be used only in one
 * project under a proprietary license, which is delivered along with this program.
 * If not, see <https://atropim.com/eula> or <https://atrodam.com/eula>.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
 */

Espo.define('export:views/channel/record/detail', 'pim:views/record/detail',
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
            this.ajaxPostRequest(`ExportFeed/${this.model.id}/exportByChannel`).then(response => {
                this.notify(this.translate(response ? 'jobCreated' : 'channelWithoutExportFeeds', 'additionalTranslates', 'ExportFeed'), response ? 'success' : 'danger');
                Backbone.trigger('showQueuePanel');
            }).always(() => {
                button.prop('disabled', false);
            });
        }
    })
);

