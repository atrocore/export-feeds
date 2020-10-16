

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

Espo.define('export:views/export-feed/fields/export-by', 'views/fields/enum',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:field', () => {
                if (this.model.get('field')) {
                    this.setupOptions();
                    this.reRender();
                }
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
            let result = {};
            let fieldLinkDefs = this.getMetadata().get(['entityDefs', this.model.get('entity'), 'links', this.model.get('field')]);
            if (fieldLinkDefs) {
                let entity = this.getMetadata().get(['clientDefs', 'ExportFeed', 'customEntities', this.model.get('entity'), this.model.get('field'), 'entity'])
                    || fieldLinkDefs.entity;
                if (entity) {
                    let fields = this.getMetadata().get(['entityDefs', entity, 'fields']) || {};
                    result = Object.keys(fields)
                        .filter(name => ['varchar'].includes(fields[name].type) && !fields[name].customizationDisabled &&
                            !fields[name].disabled && !fields[name].notStorable && (name === 'code' || !fields[name].emHidden))
                        .reduce((prev, curr) => {
                            prev[curr] = this.translate(curr, 'fields', entity);
                            return prev;
                        }, {'id': this.translate('id', 'fields', 'Global')});
                }
            }
            return result;
        },

    })
);