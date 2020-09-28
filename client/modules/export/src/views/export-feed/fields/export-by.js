

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