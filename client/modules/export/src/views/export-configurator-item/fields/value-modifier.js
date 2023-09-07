/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

Espo.define('export:views/export-configurator-item/fields/value-modifier', 'views/fields/array',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:name change:attributeId change:type', () => {
                this.reRender();
                this.model.set('valueModifier', null);
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.hide();
            if (this.model.get('type') === 'Field') {
                this.showViaType(this.getMetadata().get(['entityDefs', this.model.get('entity'), 'fields', this.model.get('name'), 'type']) || 'varchar')
            } else {
                if (this.model.get('attributeId')) {
                    this.ajaxGetRequest(`Attribute/${this.model.get('attributeId')}`).then(attribute => {
                        if (attribute.type) {
                            this.showViaType(attribute.type);
                        }
                    });
                }
            }
        },

        showViaType(type) {
            let modifiers = this.getMetadata().get(['export', 'valueModifiers', 'fieldTypes', type]) || [];
            let examplePrefix = '';
            if (this.model.get('entity') === 'ProductAttributeValue' && this.model.get('name') === 'value') {
                modifiers = Object.keys(this.getMetadata().get(['export', 'valueModifiers', 'modifiers']) || {});
                examplePrefix = 'shoes_color:';
            }

            if (modifiers.length !== 0) {
                let html = '<table class="table" style="margin-top: -4px;">';
                html += `<tr><th style="width: 15%">${this.translate('name', 'fields', 'Global')}</th><th>${this.translate('description', 'fields', 'Global')}</th><th>${this.translate('Example')}</th></tr>`;
                modifiers.forEach(modifier => {
                    let data = this.getMetadata().get(['export', 'valueModifiers', 'modifiersDescription', modifier]);
                    html += '<tr>';
                    html += `<td>${modifier}</td><td>${data.description}</td><td>${examplePrefix}${data.example}</td>`;
                    html += '</tr>';
                });
                html += '</table>';

                this.$el.append(html);
                this.show();
            }
        },

    })
);