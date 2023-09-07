/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

Espo.define('export:views/export-feed/fields/template', 'views/fields/text', Dep => {

    return Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            if (this.mode === 'edit' && this.model.isNew() && !this.model.get('template')) {
                this.model.set('template', '{#Site URL: \"{{config.siteUrl}}\"#}\n{#{% for entity in entities %}Name: \"{{ entity.name | escapeDoubleQuote | backslashNToBr | raw }}\"{% endfor %}#}');
            }
        },

    })
});