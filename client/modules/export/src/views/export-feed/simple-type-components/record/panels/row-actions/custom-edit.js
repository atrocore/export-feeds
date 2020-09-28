

Espo.define('export:views/export-feed/simple-type-components/record/panels/row-actions/custom-edit', 'views/record/row-actions/default',
    Dep => Dep.extend({

        getActionList() {
            let list = [];
            if (this.options.acl.edit) {
                list.push({
                    action: 'quickEdit',
                    label: 'Edit',
                    data: {
                        id: this.model.id
                    },
                    link: '#' + this.model.name + '/edit/' + this.model.id
                });
            }
            return list;
        }

    })
);
