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

Espo.define('export:views/export-configurator-item/fields/export-by', 'views/fields/multi-enum',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:name change:type change:attributeId change:attributeValue', () => {
                this.setupOptions();
                this.reRender();
                this.model.set('exportBy', null);
            });
        },

        getMaxDepth() {
            return parseInt($('.field[data-name="exportByMaxDepth"]>span').text()) || 1
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.mode !== 'list') {
                this.checkFieldVisibility();
            }

            if (this.model.get('entity') === 'ProductAttributeValue' && this.model.get('name') === 'value') {
                this.$el.append('<span style="color: #999; font-size: 12px">This is only for Attribute of type extensibleEnum or extensibleMultiEnum</span>')
            }
        },

        checkFieldVisibility() {
            if (this.isRequired()) {
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

            let entity;
            if (this.model.get('type') === 'Field') {
                entity = this.getMetadata().get(['entityDefs', this.model.get('entity'), 'links', this.model.get('name'), 'entity']);
                if (this.getMetadata().get(['entityDefs', this.model.get('entity'), 'fields', this.model.get('name'), 'extensibleEnumId'])) {
                    entity = 'ExtensibleEnumOption';
                }
                if (this.model.get('entity') === 'ProductAttributeValue' && this.model.get('name') === 'value') {
                    entity = 'ExtensibleEnumOption'
                }
            } else {
                if (this.model.get('attributeId')) {
                    let attribute = this.getAttribute(this.model.get('attributeId'));
                    if (['extensibleEnum', 'extensibleMultiEnum'].includes(attribute.type)) {
                        entity = 'ExtensibleEnumOption';
                    } else if (this.model.get('attributeValue') === 'valueUnitId') {
                        entity = 'Unit'
                    }
                }
            }

            if (entity) {
                /**
                 * For main image
                 */
                if (this.model.get('name') === 'mainImage' || ['Category', 'Product'].includes(this.model.get('entity')) && this.model.get('name') === 'image') {
                    entity = 'Asset';
                }

                let fields = this.getMetadata().get(['entityDefs', entity, 'fields']) || {};
                let sortedFields = Object.keys(fields).sort((v1, v2) => this.translate(v1, 'fields', entity).localeCompare(this.translate(v2, 'fields', entity)));

                sortedFields.forEach(field => {
                    let fieldData = fields[field];
                    if (!fieldData.disabled && !fieldData.exportDisabled && !['jsonObject', 'linkMultiple'].includes(fieldData.type)) {
                        if (fieldData.type === 'link') {
                            result = this.pushLinkFields(result, entity, field);
                        } else if (fieldData.type === 'asset') {
                            result = this.pushLinkFields(result, entity, field);
                            result[field + 'Url'] = this.translate(field, 'fields', entity) + ' ' + this.translate('url', 'fields', 'Attachment');
                        } else {
                            result[field] = this.translate(field, 'fields', entity);
                        }
                    }
                });
            }

            return result;
        },

        pushLinkFields(result, entity, field, depth = 1, fieldPrefix = "", translationPrefix = "") {
            if (depth > this.getMaxDepth()) {
                return result
            }

            let linkEntity = this.getMetadata().get(['entityDefs', entity, 'links', field, 'entity']);
            if (linkEntity) {
                result[fieldPrefix + field + 'Id'] = translationPrefix + this.translate(field, 'fields', entity) + ': ID';
                $.each(this.getMetadata().get(['entityDefs', linkEntity, 'fields']), (linkField, linkFieldDefs) => {
                    if (!linkFieldDefs.disabled && !linkFieldDefs.exportDisabled && !['jsonObject', 'linkMultiple'].includes(linkFieldDefs.type)) {
                        if (linkFieldDefs.type === 'link') {
                            this.pushLinkFields(result, linkEntity, linkField, depth + 1, fieldPrefix + field + '.', translationPrefix + this.translate(field, 'fields', entity) + ': ')
                        }
                        if (linkField === 'name') {
                            result[fieldPrefix + field + 'Name'] = translationPrefix + this.translate(field, 'fields', entity) + ': ' + this.translate(linkField, 'fields', linkEntity);
                        } else {
                            result[fieldPrefix + field + '.' + linkField] = translationPrefix + this.translate(field, 'fields', entity) + ': ' + this.translate(linkField, 'fields', linkEntity);
                        }
                    }
                });
            }

            return result;
        },

        isRequired() {
            if (this.model.get('entity') === 'ProductAttributeValue' && this.model.get('name') === 'value') {
                return true;
            }

            let type = 'varchar';
            if (this.model.get('type') === 'Field') {
                type = this.getMetadata().get(['entityDefs', this.model.get('entity'), 'fields', this.model.get('name'), 'type']);
            } else {
                if (this.model.get('attributeId')) {
                    type = this.getAttribute(this.model.get('attributeId')).type;
                    if (this.model.get('attributeValue') === 'valueUnitId') {
                        type = 'link'
                    }
                }
            }

            return ['image', 'asset', 'link', 'extensibleEnum', 'linkMultiple', 'extensibleMultiEnum'].includes(type) && (this.params.options || []).length;
        },

        getAttribute(attributeId) {
            let key = `attribute_${attributeId}`;
            if (!Espo[key]) {
                Espo[key] = null;
                this.ajaxGetRequest(`Attribute/${this.model.get('attributeId')}`, null, {async: false}).success(attr => {
                    Espo[key] = attr;
                });
            }

            return Espo[key];
        },

    })
);