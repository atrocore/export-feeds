


/*
 * This file is part of premium software, which is NOT free.
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This Software is the property of AtroCore UG (haftungsbeschränkt) and is
 * protected by copyright law - it is NOT Freeware and can be used only in one
 * project under a proprietary license, which is delivered along with this program.
 * If not, see <https://atropim.com/eula> or <https://atrodam.com/eula>.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
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
                extraInfo = `${this.translate('exportBy', 'fields', 'ExportFeed')}: ${exportByTranslation}`;
            }

            if (this.model.get('attributeId')) {
                extraInfo = `${this.translate('Attribute', 'scopeNames', 'Global')}`;
            }

            return extraInfo;
        },

        getExportByTranslation() {
            let translation;
            let field = this.model.get('exportBy');
            if (field === 'id') {
                translation = this.translate('id', 'fields', 'Global');
            } else {
                let entity = this.getMetadata().get(['entityDefs', this.model.get('entity'), 'links', this.model.get('field'), 'entity']);
                if (entity) {
                    translation = this.translate(field, 'fields', entity);
                }
            }
            return translation;
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