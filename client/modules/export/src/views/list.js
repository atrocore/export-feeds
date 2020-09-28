

Espo.define('export:views/list', 'views/list',
    Dep => Dep.extend({

        setup() {
            this.quickCreate = this.getMetadata().get(`clientDefs.${this.scope}.quickCreate`);

            Dep.prototype.setup.call(this);
        },

        navigateToEdit(id) {
            let router = this.getRouter();

            router.dispatch(this.scope, 'view', {
                id: id,
                setEditMode: true,
                optionsToPass: ['setEditMode'],
                model: this.collection.get(id)
            });
            router.navigate(`#${this.scope}/view/${id}`, {trigger: false});
        },

        actionQuickCreate() {
            let options = _.extend({
                scope: this.scope,
                attributes: this.getCreateAttributes() || {}
            }, this.getMetadata().get(`clientDefs.${this.scope}.quickCreateOptions`) || {})

            this.notify('Loading...');
            this.createView('quickCreate', 'views/modals/edit', options, function (view) {
                view.render();
                view.notify(false);
                this.listenToOnce(view, 'after:save', function () {
                    let id = view.getView('edit').model.id;
                    this.collection.fetch().then(() => {
                        if (this.getMetadata().get(`clientDefs.${this.scope}.navigateToEntityAfterQuickCreate`)) {
                            this.navigateToEdit(id);
                        }
                    });
                }, this);
            }.bind(this));
        }
    })
);

