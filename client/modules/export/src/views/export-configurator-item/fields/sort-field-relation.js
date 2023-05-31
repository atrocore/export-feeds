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

Espo.define('export:views/export-configurator-item/fields/sort-field-relation', 'views/fields/enum',
    Dep => Dep.extend({

        prohibitedEmptyValue: true,

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:name change:type', () => {
                this.setupOptions();
                this.reRender();
                this.model.set('sortFieldRelation', null);
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.mode !== 'list') {
                this.checkFieldVisibility();
            }
        },

        checkFieldVisibility() {
            let type = this.getMetadata().get(['entityDefs', this.model.get('entity'), 'fields', this.model.get('name'), 'type']);

            if (this.model.get('type') === 'Field' && ['linkMultiple', 'extensibleMultiEnum'].includes(type)) {
                this.show();
            } else {
                this.hide();
            }
        },

        setupOptions() {
            this.translatedOptions = {'': '', 'id': this.translate('id', 'fields', 'Global')};

            let entity = this.getMetadata().get(['entityDefs', this.model.get('entity'), 'links', this.model.get('name'), 'entity']);
            if (this.getMetadata().get(['entityDefs', this.model.get('entity'), 'fields', this.model.get('name'), 'extensibleEnumId'])) {
                entity = 'ExtensibleEnumOption';
            }

            if (entity) {
                let fields = this.getMetadata().get(['entityDefs', entity, 'fields']) || {};
                let notAllowedType = ['jsonObject', 'linkMultiple'];
                $.each(fields, function (field, fieldData) {
                    if (fieldData.notStorable !== true && !notAllowedType.includes(fieldData.type) && fieldData.exportDisabled !== true) {
                        if (fieldData.type === 'link' || fieldData.type === 'asset') {
                            this.translatedOptions[field + 'Id'] = this.translate(field, 'fields', entity) + ' ID';

                            // add relation fields
                            let foreignEntity = this.getMetadata().get(['entityDefs', entity, 'links', field, 'entity']);
                            if (foreignEntity) {
                                let foreignFields = this.getMetadata().get(['entityDefs', foreignEntity, 'fields']) || {};
                                $.each(foreignFields, function (foreignField, foreignFieldData) {
                                    if (foreignFieldData.notStorable !== true && !notAllowedType.includes(foreignFieldData.type) && foreignFieldData.exportDisabled !== true) {
                                        if (foreignFieldData.type === 'link' || foreignFieldData.type === 'asset') {
                                            this.translatedOptions[field + '.' + foreignField + 'Id'] = this.translate(field, 'fields', entity) + ': ' + this.translate(foreignField, 'fields', foreignEntity) + ' ID';
                                        } else {
                                            this.translatedOptions[field + '.' + foreignField] = this.translate(field, 'fields', entity) + ': ' + this.translate(foreignField, 'fields', foreignEntity);
                                        }
                                    }
                                }.bind(this));
                            }
                        } else {
                            this.translatedOptions[field] = this.translate(field, 'fields', entity);
                        }
                    }
                }.bind(this));
            }

            this.params.options = Object.keys(this.translatedOptions);
        },

    })
);