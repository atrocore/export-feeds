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

Espo.define('export:views/export-feed/fields/varchar-with-info', 'views/fields/varchar',
    Dep => Dep.extend({

        detailTemplate: 'export:export-feed/fields/varchar-with-info/detail',

        listTemplate: 'export:export-feed/fields/varchar-with-info/detail',

        data() {
            let data = Dep.prototype.data.call(this);
            data.extraInfo = this.getExtraInfo();
            data.isNotEmpty = true;
            return data;
        },

        getExtraInfo() {
            let extraInfo = null;

            let exportByTranslation = this.getExportByTranslation();
            if (this.model.get('exportBy') && exportByTranslation) {
                extraInfo = `${this.translate('fields', 'labels', 'ExportFeed')}: ${exportByTranslation}`;
                if (this.model.get('exportIntoSeparateColumns')) {
                    extraInfo += `<br>${this.translate('exportIntoSeparateColumns', 'fields', 'ExportFeed')}`;
                }
            }

            if (this.model.get('useAttributeNameAsColumnName')) {
                extraInfo += `<br>${this.translate('useAttributeNameAsColumnName', 'fields', 'ExportFeed')}`;
            }

            if (this.model.get('attributeId')) {
                extraInfo = `${this.translate('Attribute', 'scopeNames', 'Global')}`;
            }

            return extraInfo;
        },

        getExportByTranslation() {
            let translations = [];

            let fields = this.model.get('exportBy') || [];

            fields.forEach(function (field) {
                if (field === 'id') {
                    translations.push(this.translate('id', 'fields', 'Global'));
                } else {
                    let entity = this.getMetadata().get(['entityDefs', this.model.get('entity'), 'links', this.model.get('field'), 'entity']);
                    if (entity) {
                        if (field.substring(field.length - 2) === 'Id') {
                            translations.push(this.translate(field.substring(0, field.length - 2), 'fields', entity));
                        } else {
                            translations.push(this.translate(field, 'fields', entity));
                        }
                    }
                }
            }, this);

            return translations.join(', ');
        },

        getValueForDisplay() {
            let value = this.translate(this.model.get(this.name), 'fields', this.model.get('entity'));
            if (this.model.get('attributeId')) {
                value = this.model.get('attributeName');
            }
            return value;
        }

    })
);