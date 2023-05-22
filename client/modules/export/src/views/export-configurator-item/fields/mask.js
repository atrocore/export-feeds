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

                    if (fieldDefs.measureId) {
                        this.setRequired();
                        this.show();
                        this.model.set('mask', '{{value}} {{unit}}');
                    }
                }
            } else if (this.model.get('type') === 'Attribute' && this.model.get('attributeId')) {
                this.ajaxGetRequest(`Attribute/${this.model.get('attributeId')}`).then(attribute => {
                    if (attribute.type === 'currency') {
                        this.setRequired();
                        this.show();
                        this.model.set('mask', '{{value}} {{currency}}');
                    }

                    if (attribute.measureId) {
                        this.setRequired();
                        this.show();
                        this.model.set('mask', '{{value}} {{unit}}');
                    }
                });
            }
        },

    })
);