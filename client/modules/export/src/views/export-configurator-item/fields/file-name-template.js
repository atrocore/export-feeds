/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

Espo.define('export:views/export-configurator-item/fields/file-name-template', ['views/fields/script', 'export:views/export-configurator-item/fields/zip'],
    (Dep, ZipField) => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:name change:type change:zip', () => {
                this.setDefaultVal();
                this.reRender();
            });
        },

        setDefaultVal() {
            if (ZipField.prototype.hasZip.call(this) && (!this.model.get('fileNameTemplate') || this.model.get('fileNameTemplate') === '')) {
                this.model.set('fileNameTemplate', '{{ fileName }}');
            }
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (ZipField.prototype.hasZip.call(this) && this.model.get('zip')) {
                this.show();
            } else {
                this.hide();
            }
        },

    })
);