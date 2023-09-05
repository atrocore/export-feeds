/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.md, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

Espo.define('export:views/export-configurator-item/fields/mask', 'views/fields/varchar',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:name change:attributeId change:type', () => {
                this.reRender();
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.mode !== 'list') {
                this.checkFieldVisibility();
            }
        },

        checkFieldVisibility() {
            this.setNotRequired();
            this.hide();

            if (this.model.get('type') === 'Field') {
                let fieldDefs = this.getMetadata().get(['entityDefs', this.model.get('entity'), 'fields', this.model.get('name')]);
                if (fieldDefs) {
                    if (fieldDefs.type === 'currency') {
                        this.setRequired();
                        this.show();
                        this.model.set('mask', '{{value}} {{currency}}');
                    }
                }
            } else if (this.model.get('type') === 'Attribute' && this.model.get('attributeId')) {
                this.ajaxGetRequest(`Attribute/${this.model.get('attributeId')}`).then(attribute => {
                    if (attribute.type === 'currency') {
                        this.setRequired();
                        this.show();
                        this.model.set('mask', '{{value}} {{currency}}');
                    }
                });
            }
        },

    })
);