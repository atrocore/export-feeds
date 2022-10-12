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

Espo.define('export:views/export-configurator-item/fields/name', 'views/fields/enum',
    Dep => Dep.extend({

        listTemplate: 'export:export-configurator-item/fields/name/list',

        prohibitedEmptyValue: true,

        setup() {
            let entity = this.model.get('entity');
            let fields = this.getEntityFields(entity);

            this.params.options = [];
            this.translatedOptions = {};

            $.each(fields, field => {
                this.params.options.push(field);
                this.translatedOptions[field] = this.translate(field, 'fields', entity);
            });

            // select first
            if (!this.model.get('name') && this.params.options[0]) {
                this.model.set('name', this.params.options[0]);
            }

            this.listenTo(this.model, 'change:type', () => {
                if (this.model.get('type') === 'Fixed value') {
                    this.model.set(this.name, null);
                }
                this.reRender();
            })


            Dep.prototype.setup.call(this);
        },

        data() {
            let data = Dep.prototype.data.call(this);

            if (this.mode === 'list') {
                data.extraInfo = this.getExtraInfo();
            }

            return data;
        },

        getValueForDisplay() {
            let name = this.model.get('name');

            if (this.mode !== 'list') {
                return name;
            }

            if (this.model.get('type') === 'Field') {
                name = this.translate(name, 'fields', this.model.get('entity'));
            }

            if (this.model.get('type') === 'Attribute') {
                name = this.model.get('attributeNameValue');

                if (this.model.get('isAttributeMultiLang') && this.model.get('locale') !== 'mainLocale') {
                    name += ' / ' + this.model.get('locale');
                }
            }
            if (this.model.get('type') === 'Fixed value') {
                name = this.getLanguage().translate('fixedValue', 'fields', 'ExportConfiguratorItem');
            }


            return name;
        },

        getExtraInfo() {
            let extraInfo = '';

            let exportByTranslation = this.getExportByTranslation();
            if (exportByTranslation) {
                extraInfo += `${this.translate('fields', 'labels', 'ExportFeed')}: ${exportByTranslation}`;
                if (this.model.get('exportIntoSeparateColumns')) {
                    extraInfo += `<br>${this.translate('exportIntoSeparateColumns', 'fields', 'ExportConfiguratorItem')}`;
                }
            }

            if (this.model.get('attributeId')) {
                extraInfo += `${this.translate('code', 'fields', 'Attribute')}: ${this.model.get('attributeCode')}`;
                extraInfo += `<br>${this.translate('scope', 'fields', 'ExportConfiguratorItem')}: `;

                if (this.model.get('scope') === 'Global') {
                    extraInfo += 'Global';
                } else {
                    extraInfo += this.model.get('channelName');
                }
            }

            return extraInfo;
        },

        getExportByTranslation() {
            let translations = [];
            (this.model.get('exportBy') || []).forEach(field => {
                if (field === 'id') {
                    translations.push(this.translate('id', 'fields', 'Global'));
                } else {
                    let entity = this.getMetadata().get(['entityDefs', this.model.get('entity'), 'links', this.model.get('name'), 'entity']);
                    if (entity) {
                        let parts = field.split('.');
                        if (field.substring(field.length - 2) === 'Id') {
                            translations.push(this.translate(field.substring(0, field.length - 2), 'fields', entity) + ' ' + this.translate('id', 'fields', 'Global'));
                        } else if (field.substring(field.length - 4) === 'Name') {
                            translations.push(this.translate(field.substring(0, field.length - 4), 'fields', entity) + ' ' + this.translate('name', 'fields', 'Global'));
                        } else if (field.substring(field.length - 3) === 'Url') {
                            translations.push(this.translate(field.substring(0, field.length - 3), 'fields', entity) + ' ' + this.translate('url', 'fields', 'Attachment'));
                        } else if (parts.length === 2) {
                            let linkEntity = this.getMetadata().get(['entityDefs', entity, 'links', parts[0], 'entity']);
                            if (linkEntity) {
                                translations.push(this.translate(parts[0], 'fields', entity) + ' ' + this.translate(parts[1], 'fields', linkEntity));
                            }
                        } else {
                            translations.push(this.translate(field, 'fields', entity));
                        }
                    }
                }
            });

            return translations.join(', ');
        },

        getEntityFields(entity) {
            let result = {
                id: {
                    type: "varchar"
                }
            };

            let notExportedType = [
                'linkParent',
                'currencyConverted',
                'available-currency',
                'file',
                'attachmentMultiple'
            ];

            if (entity) {
                let fields = this.getMetadata().get(['entityDefs', entity, 'fields']);
                Object.keys(fields).forEach(name => {
                    let field = fields[name];
                    if (!notExportedType.includes(field.type) && !field.disabled && !field.exportDisabled) {
                        result[name] = fields[name];
                    }
                });
            }

            return result;
        },

    })
);