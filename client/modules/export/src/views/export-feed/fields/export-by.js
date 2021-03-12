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

Espo.define('export:views/export-feed/fields/export-by', 'views/fields/multi-enum',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:field', () => {
                this.setupOptions();
                this.reRender();
                this.model.set('exportBy', null);
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'edit' || this.mode === 'detail') {
                this.checkFieldVisibility();
            }
        },

        checkFieldVisibility() {
            let fieldDefs = this.getMetadata().get(['entityDefs', this.model.get('entity'), 'fields', this.model.get('field')]);
            if (fieldDefs && ['link', 'linkMultiple'].includes(fieldDefs.type) && (this.params.options || []).length) {
                this.show();
            } else {
                this.hide();
            }
        },

        setupOptions() {
            let translatedOptions = this.getTranslatesForExportByField();
            this.params.options = Object.keys(translatedOptions);
            this.translatedOptions = this.params.translatedOptions = translatedOptions;
        },

        getTranslatesForExportByField() {
            let result = {'id': this.translate('id', 'fields', 'Global')};
            let fieldLinkDefs = this.getMetadata().get(['entityDefs', this.model.get('entity'), 'links', this.model.get('field')]);
            if (fieldLinkDefs) {
                let entity = this.getMetadata().get(['clientDefs', 'ExportFeed', 'customEntities', this.model.get('entity'), this.model.get('field'), 'entity'])
                    || fieldLinkDefs.entity;
                if (entity) {
                    let fields = this.getMetadata().get(['entityDefs', entity, 'fields']) || {};

                    $.each(fields, function (field, fieldData) {
                        if (!fieldData.disabled && !fieldData.notStorable && !fieldData.exportDisabled) {
                            if (['varchar'].includes(fieldData.type)) {
                                result[field] = this.translate(field, 'fields', entity);
                            }
                            if (fieldData.type === 'link') {
                                result[field + 'Id'] = this.translate(field, 'fields', entity);
                            }
                        }
                    }.bind(this));
                }
            }

            return result;
        },

    })
);