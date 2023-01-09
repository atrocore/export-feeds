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

Espo.define('export:views/export-configurator-item/fields/previous-item', 'views/fields/enum',
    Dep => Dep.extend({

        setup() {
            this.params.options = [""];
            this.translatedOptions = {"": ""};

            this.ajaxGetRequest(`ExportFeed/${this.model.get('exportFeedId')}/configuratorItems`, {
                offset: 0,
                maxSize: 9999,
                sortBy: "sortOrder",
                asc: true
            }).success(response => {
                if (response.total) {
                    let i = 1;
                    let previousItem = null;
                    response.list.forEach(item => {
                        if (item.id !== this.model.get('id')) {
                            this.params.options.push(item.id);
                            this.translatedOptions[item.id] = i + '. ' + this.prepareNameLabel(item);
                        } else {
                            this.model.set('previousItem', previousItem);
                        }
                        previousItem = item.id;
                        i++;
                    });

                    if (this.model.isNew()) {
                        this.model.set('previousItem', previousItem);
                    }
                    this.reRender();
                }
            });

            Dep.prototype.setup.call(this);
        },

        prepareNameLabel(object) {
            let name = object.name;

            if (object.type === 'Field') {
                name = this.translate(name, 'fields', object.entity);
            }

            if (object.type === 'Attribute') {
                name = object.attributeNameValue;

                if (object.isAttributeMultiLang && object.locale !== 'mainLocale') {
                    name += ' / ' + object.locale;
                }
            }

            if (object.type === 'Fixed value') {
                name = this.getLanguage().translate('fixedValue', 'fields', 'ExportConfiguratorItem');
            }

            return name;
        },

    })
);