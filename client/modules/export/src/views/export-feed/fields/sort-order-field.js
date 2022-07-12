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

Espo.define('export:views/export-feed/fields/sort-order-field', 'views/fields/enum',
    Dep => {

        return Dep.extend({

            prohibitedEmptyValue: false,

            setup() {
                Dep.prototype.setup.call(this);

                this.listenTo(this.model, 'change:entity', () => {
                    this.setupOptions();
                    this.reRender();
                    this.model.set('sortOrderField', null);
                });
            },

            setupOptions() {
                let scope = this.model.get('entity');
                let fieldDefs = this.getMetadata().get(`entityDefs.${scope}.fields`) || {};

                this.params.options = [];
                this.translatedOptions = {};

                $.each(fieldDefs, (field, defs) => {
                    if (defs.notStorable) {
                        return;
                    }

                    if (defs.type === 'linkMultiple') {
                        return;
                    }

                    this.params.options.push(field);
                    this.translatedOptions[field] = this.translate(field, 'fields', scope);
                });
            },

        })
    });