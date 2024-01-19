/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

Espo.define('export:views/export-configurator-item/fields/name', 'views/fields/enum',
    Dep => Dep.extend({

        listTemplate: 'export:export-configurator-item/fields/name/list',

        prohibitedEmptyValue: true,

        setup() {
            let entity = this.model.get('entity');
            let fields = this.getFieldsList(entity);
            let sortedFields = Object.keys(fields).sort((v1, v2) => this.translate(v1, 'fields', entity).localeCompare(this.translate(v2, 'fields', entity)));

            this.params.options = [];
            this.translatedOptions = {};

            sortedFields.forEach(field => {
                this.params.options.push(field);
                this.translatedOptions[field] = this.translate(field, 'fields', entity);
            });

            // select first
            if (!this.model.get('name') && this.params.options[0]) {
                this.model.set('name', this.params.options[0]);
            }

            this.listenTo(this.model, 'change:type', () => {
                if (this.model.get('type') === 'Fixed value' || this.model.get('type') === 'script') {
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
                if (!this.model.get('exportFeedLanguage') && this.getMetadata().get(`entityDefs.${this.model.get('entity')}.fields.${this.model.get('name')}.isMultilang`) && this.model.get('language') !== 'main') {
                    name += ' / ' + this.model.get('language');
                }
            }

            if (this.model.get('type') === 'Attribute') {
                name = this.model.get('attributeNameValue');
                if (!this.model.get('exportFeedLanguage') && this.model.get('isAttributeMultiLang') && this.model.get('language') !== 'main') {
                    name += ' / ' + this.model.get('language');
                }
            }

            if (this.model.get('type') === 'Fixed value') {
                name = this.getLanguage().translate('fixedValue', 'fields', 'ExportConfiguratorItem');
            }

            if (this.model.get('type') === 'script') {
                name = this.getLanguage().translate('script', 'fields', 'ExportConfiguratorItem');
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
                if (this.model.get('zip')) {
                    extraInfo += '<br> Zip'
                }
                if (this.model.get('attributeId')) {
                    extraInfo += '<br>';
                }
            }

            if (this.model.get('attributeId')) {
                extraInfo += `${this.translate('code', 'fields', 'Attribute')}: ${this.model.get('attributeCode')}`;
                if (this.model.get('attributeValue')) {
                    extraInfo += '<br>' + this.translate('attributeValue', 'fields', 'ExportConfiguratorItem') + ': ' + this.getLanguage().translateOption(this.model.get('attributeValue'), 'attributeValue', 'ExportConfiguratorItem')
                }
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
                        if (this.model.get('attributeId') && this.model.get('attributeType')) {
                            if (['extensibleEnum', 'extensibleMultiEnum'].includes(this.model.get('attributeType'))) {
                                entity = 'ExtensibleEnumOption';
                            } else if (this.model.get('attributeValue') === 'valueUnit') {
                                entity = 'Unit'
                            }
                        }
                    }

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

        getFieldsList(entity) {
            let result = {
                id: {
                    type: "varchar"
                }
            };

            let notExportedType = [
                'linkParent',
                'currencyConverted',
                'attachmentMultiple'
            ];

            if (entity) {
                let fields = this.getMetadata().get(['entityDefs', entity, 'fields']) || [];
                Object.keys(fields).forEach(name => {
                    let field = fields[name];
                    if (!notExportedType.includes(field.type) && !field.disabled && !field.exportDisabled && !field.multilangField && (!field.language || field.language === 'main')) {
                        result[name] = fields[name];
                    }
                });
            }

            return result;
        },

    })
);