

Espo.define('export:views/export-feed/record/list', 'views/record/list',
    Dep => Dep.extend({

        rowActionsView: 'export:views/export-feed/record/row-actions/default',

        actionExportNow(data) {
            const model = this.collection.get(data.id);

            this.ajaxPostRequest(`ExportFeed/${model.id}/exportByFeed`).then(response => {
                this.notify(this.translate(response ? 'jobCreated' : 'jobNotCreated', 'additionalTranslates', 'ExportFeed'), response ? 'success' : 'danger');
                Backbone.trigger('showQueuePanel');
            });
        }

    })
);
