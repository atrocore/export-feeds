/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.md, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

Espo.define('export:views/export-configurator-item/fields/type', 'views/fields/enum',
    Dep => Dep.extend({

        setupOptions() {
            Dep.prototype.setupOptions.call(this);

            if (this.model.get('entity') !== 'Product') {
                const key = this.params.options.findIndex(option => {
                    return option === 'Attribute';
                });

                if (key !== -1) {
                    this.params.options.splice(key, 1);
                }
            }
        }

    })
);