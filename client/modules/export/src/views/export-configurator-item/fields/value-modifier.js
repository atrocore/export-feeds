/*
 * Export Feeds
 * Free Extension
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

Espo.define('export:views/export-configurator-item/fields/value-modifier', 'views/fields/text',
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
            if (modifiers.length !== 0) {
                let html = '<table class="table" style="margin-top: -4px;">';
                html += `<tr><th style="width: 15%">${this.translate('name', 'fields', 'Global')}</th><th>${this.translate('description', 'fields', 'Global')}</th><th>${this.translate('Example')}</th></tr>`;
                modifiers.forEach(modifier => {
                    let data = this.getMetadata().get(['export', 'valueModifiers', 'modifiersDescription', modifier]);
                    html += '<tr>';
                    html += `<td>${modifier}</td><td>${data.description}</td><td>||${data.example}</td>`;
                    html += '</tr>';
                });
                html += '</table>';

                this.$el.append(html);
                this.show();
            }
        },

    })
);