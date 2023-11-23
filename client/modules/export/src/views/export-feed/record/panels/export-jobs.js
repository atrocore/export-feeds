/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

Espo.define('export:views/export-feed/record/panels/export-jobs', 'views/record/panels/relationship',
    Dep => Dep.extend({

        refreshIntervalGap: 3000,

        refreshInterval: null,

        pauseRefreshInterval: false,

        setup() {
            Dep.prototype.setup.call(this);

            this.listenToOnce(this, 'after:render', () => {
                if (this.collection && this.hasPanel()) {
                    this.refreshInterval = window.setInterval(() => {
                        if (!this.pauseRefreshInterval && $(`div[data-name='exportJobs'] .open a[data-action='removeRelated']`).length === 0) {
                            this.actionRefresh();
                        }
                    }, this.refreshIntervalGap);

                    this.listenTo(this.collection, 'pauseRefreshInterval', value => {
                        this.pauseRefreshInterval = value;
                    });
                }
            });

            this.listenToOnce(this, 'remove', () => {
                if (this.refreshInterval) {
                    window.clearInterval(this.refreshInterval);
                }
            });
        },

        actionRefresh() {
            this.pauseRefreshInterval = true;
            this.collection.fetch().then(() => this.pauseRefreshInterval = false);
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.$el.parent().hide();
            if (this.hasPanel()) {
                this.$el.parent().show();
            }
        },

        hasPanel() {
            return true;
        },

        actionCancelExportJob(data) {
            let model = this.collection.get(data.id);

            this.notify('Saving...');
            model.set('state', 'Canceled');
            model.save().then(() => {
                this.notify('Saved', 'success');
            });
        },

        actionTryAgainExportJob(data) {
            let model = this.collection.get(data.id);

            this.notify('Saving...');
            model.set('state', 'Pending');
            model.save().then(() => {
                this.notify('Saved', 'success');
            });
        },

    })
);