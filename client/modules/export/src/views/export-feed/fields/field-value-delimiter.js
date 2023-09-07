/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

Espo.define('export:views/export-feed/fields/field-value-delimiter', 'views/fields/varchar',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.validations = Espo.Utils.clone(this.validations);
            if (!this.validations.includes('delimiters')) {
                this.validations.push('delimiters');
            }
        },

        validateDelimiters() {
            return false;
        }

    })
);