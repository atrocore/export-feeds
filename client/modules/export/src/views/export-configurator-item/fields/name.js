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

            // if (this.model.get('type') === 'Attribute' && this.model.get('attributeIsMultilang') && this.model.get('locale') !== 'main') {
            //     name += ' › ' + this.model.get('locale');
            // }

            return name;
        },

        getExtraInfo() {
            let extraInfo = null;

            // let exportByTranslation = this.getExportByTranslation();
            // if (this.model.get('exportBy') && exportByTranslation) {
            //     extraInfo = `${this.translate('fields', 'labels', 'ExportFeed')}: ${exportByTranslation}`;
            //     if (this.model.get('exportIntoSeparateColumns')) {
            //         extraInfo += `<br>${this.translate('exportIntoSeparateColumns', 'fields', 'ExportFeed')}`;
            //
            //         if (this.model.get('entity') === 'Product' && this.model.get('field') === 'productAttributeValues') {
            //             if (this.model.get('attributeColumn') === 'attributeName') {
            //                 extraInfo += `<br>${this.translate('useAttributeNameAsColumnName', 'labels', 'ExportFeed')}`;
            //             }
            //             if (this.model.get('attributeColumn') === 'internalAttributeName') {
            //                 extraInfo += `<br>${this.translate('useInternalAttributeNameAsColumnName', 'labels', 'ExportFeed')}`;
            //             }
            //             if (this.model.get('attributeColumn') === 'attributeCode') {
            //                 extraInfo += `<br>${this.translate('useAttributeCodeAsColumnName', 'labels', 'ExportFeed')}`;
            //             }
            //         }
            //     }
            // }
            //
            // if (this.model.get('attributeId')) {
            //     extraInfo = `${this.translate('Attribute', 'scopeNames', 'Global')}`;
            // }

            return extraInfo;
        },

        getEntityFields(entity) {
            let result = {};

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
                    if (!notExportedType.includes(field.type) && !field.emHidden && !field.disabled && !field.exportDisabled) {
                        result[name] = fields[name];
                    }
                });
            }

            return result;
        },

    })
);