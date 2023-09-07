/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
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
        }
    });

});