

Espo.define('export:views/export-feed/fields/remove', 'view', function (Dep) {

    return Dep.extend({

        template: 'export:export-feed/fields/remove/list',

        buttonDisabled: false,

        events: {
            'click button[data-action="actionRemove"]': function () {
                if (!this.buttonDisabled) {
                    this.model.collection.trigger('actionRemove', this.model);
                }
            }
        },

        data() {
            return {
                disabled: this.buttonDisabled
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.buttonDisabled = !this.getAcl().check('ExportFeed', 'edit');

            this.listenTo(this.model.collection, 'model-removing', () => {
                this.buttonDisabled = true;
                this.$el.find('button').prop('disabled', true);
            });

            this.listenTo(this.model.collection, 'after:model-removing', () => {
                this.buttonDisabled = false;
                this.$el.find('button').prop('disabled', false);
            });
        }
    })
});