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

Espo.define('export:views/export-feed/fields/attribute-column', 'views/fields/enum',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:field', () => {
                if (!this.model.get('attributeColumn')) {
                    this.model.set('attributeColumn', 'attributeName');
                }
            });

            this.listenTo(this.model, 'change:field change:exportIntoSeparateColumns', () => {
                this.reRender();
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'edit' || this.mode === 'detail') {
                this.checkFieldVisibility();
            }
        },

        isRequired() {
            return true;
        },

        checkFieldVisibility() {
            if (this.isVisible()) {
                this.$el.show();
            } else {
                this.$el.hide();
            }
        },

        isVisible() {
            return this.model.get('entity') === 'Product' && this.model.get('field') === 'productAttributeValues' && this.model.get('exportIntoSeparateColumns')
        },

        validateRequired: function () {
            if (!this.isVisible()) {
                return false;
            }
            Dep.prototype.validateRequired.call(this);
        },

    })
);