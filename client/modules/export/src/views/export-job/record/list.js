/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

Espo.define('export:views/export-job/record/list', 'views/record/list',
    Dep => Dep.extend({
        rowActionsView: 'export:views/export-job/record/row-actions/export-again-and-remove',

        actionTryAgainExportJob(data) {
            let model = this.collection.get(data.id);

            this.notify('Saving...');
            model.set('state', 'Pending');
            model.save().then(() => {
                this.notify('Saved', 'success');
            });
        }
    })
);