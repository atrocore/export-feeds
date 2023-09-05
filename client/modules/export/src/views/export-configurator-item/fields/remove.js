/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.md, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
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