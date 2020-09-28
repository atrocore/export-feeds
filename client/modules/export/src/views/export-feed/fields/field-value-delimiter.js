

Espo.define('export:views/export-feed/fields/field-value-delimiter', 'views/fields/varchar',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.validations = Espo.Utils.clone(this.validations);
            if (!this.validations.includes('delimiters')) {
                this.validations.push('delimiters');
            }
        },

        validateDelimiters() {
            if (this.model.get('csvFieldDelimiter') === this.model.get('valueDelimiter')) {
                this.trigger('invalid');
                let msg = this.translate('delimitersMustBeDifferent', 'messages', this.model.name);
                this.showValidationMessage(msg);
                return true;
            }
        }

    })
);