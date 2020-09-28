

Espo.define('export:views/channel/record/detail', 'pim:views/record/detail',
    Dep => Dep.extend({

        template: 'export:channel/record/detail',

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.handleExportButtonVisibility();
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'after:save', () => {
                this.handleExportButtonVisibility();
            });
        },

        handleExportButtonVisibility() {
            const button = this.$el.find('button[data-action="exportByChannel"]');
            this.model.get('isActive') ? button.removeClass('hidden') : button.addClass('hidden');
        },

        actionExportByChannel() {
            const button = this.$el.find('button[data-action="exportByChannel"]');
            button.prop('disabled', true);
            this.notify('Please wait');
            this.ajaxPostRequest(`ExportFeed/${this.model.id}/exportByChannel`).then(response => {
                this.notify(this.translate(response ? 'jobCreated' : 'channelWithoutExportFeeds', 'additionalTranslates', 'ExportFeed'), response ? 'success' : 'danger');
                Backbone.trigger('showQueuePanel');
            }).always(() => {
                button.prop('disabled', false);
            });
        }
    })
);

