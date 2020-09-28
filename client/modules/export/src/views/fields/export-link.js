

Espo.define('export:views/fields/export-link', 'views/fields/varchar',
    Dep => Dep.extend({
        listTemplate: 'export:fields/export-link/detail',

        detailTemplate: 'export:fields/export-link/detail',

        editTemplate: 'export:fields/export-link/detail',

        events: {
            'click .action[data-action="setUrl"]': function () {
                this.actionSetLink();
            }
        },

        actionSetLink() {
            let url = this.model.getFieldParam(this.name, 'dataUrl');
            if (url) {
                this.ajaxGetRequest(url).then(function (response) {
                    if (response.link) {
                        this.model.set({[this.name]: response.link});
                    }
                }.bind(this));
            }
        }
    })
);
