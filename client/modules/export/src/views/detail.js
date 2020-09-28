

Espo.define('export:views/detail', 'views/detail',
    Dep => Dep.extend({

        setup() {
            this.quickCreate = this.getMetadata().get(`clientDefs.${this.scope}.quickCreate`);

            (this.options.params.optionsToPass || []).forEach(item => {
                if (!this.optionsToPass.includes(item)) {
                    this.optionsToPass.push(item);
                }

                this.options[item] = this.options.params[item];
            });

            Dep.prototype.setup.call(this);
        }
    })
);

