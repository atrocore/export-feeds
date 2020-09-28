

Espo.define('export:views/record/detail', 'views/record/detail',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            if (this.options.setEditMode) {
                this.listenToOnce(this, 'after:render', () => this.actionEdit());
            }
        },

    })
);