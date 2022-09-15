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

Espo.define('export:views/export-configurator-item/fields/column-type', 'views/fields/enum',
    Dep => {

        return Dep.extend({

            prohibitedEmptyValue: true,

            init: function () {
                Dep.prototype.init.call(this);

                this.listenTo(this.model, 'change:name', () => {
                    this.reRender();
                });

                this.listenTo(this.model, 'change:type', () => {
                    if (this.model.get('type') === 'Fixed value') {
                        this.model.set(this.name, 'custom');
                    } else {
                        this.model.set(this.name, 'name');
                    }
                    this.reRender();
                })
            },

            setupOptions() {
                this.params.options = ['name', 'internal', 'custom'];
                this.translatedOptions = {
                    name: this.translate('name', 'columnType', 'ExportConfiguratorItem'),
                    internal: this.translate('internal', 'columnType', 'ExportConfiguratorItem'),
                    custom: this.translate('custom', 'columnType', 'ExportConfiguratorItem'),
                };
            },

            afterRender() {
                Dep.prototype.afterRender.call(this);

                if (this.mode !== 'list') {
                    this.checkFieldVisibility();
                    this.checkFieldDisability();
                }
            },

            checkFieldVisibility() {
                this.$el.show();
            },

            checkFieldDisability() {
                if (this.model.get('type') === 'Fixed value') {
                    this.$el.find('select').attr('disabled', 'disabled');
                } else {
                    this.$el.find('select').removeAttr('disabled');
                }
            },

        })
    });