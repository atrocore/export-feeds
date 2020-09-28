

Espo.define('export:views/export-feed/simple-type-components/modals/attribute-edit', 'export:views/export-feed/simple-type-components/modals/field-edit',
    Dep => Dep.extend({

        template: 'export:export-feed/simple-type-components/modals/attribute-edit',

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:attributeId', () => {
                this.model.set({
                    column: this.getColumnFromAttribute(),
                    scope: 'Global',
                    channelId: null,
                    channelName: null
                });
            });

            this.listenTo(this.model, 'change:scope change:channelId', () => {
                this.model.set({column: this.getColumnFromAttribute()});
            });
        },

        createBaseFields() {
            this.createView('attribute', 'views/fields/link', {
                model: this.model,
                name: 'attribute',
                el: `${this.options.el} .field[data-name="attribute"]`,
                mode: 'edit',
                params: {
                    required: true
                },
                foreignScope: 'Attribute',
                createDisabled: true
            }, view => {});

            this.createView('column', 'export:views/export-feed/fields/column', {
                model: this.model,
                name: 'column',
                el: `${this.options.el} .field[data-name="column"]`,
                mode: 'edit',
                params: {
                    required: true
                }
            }, view => {});


            this.createView('scope', 'views/fields/enum', {
                model: this.model,
                name: 'scope',
                el: `${this.options.el} .field[data-name="scope"]`,
                mode: 'edit',
                params: {
                    options: ['Global', 'Channel']
                }
            }, view => {
                this.listenTo(this.model, 'change:scope', () => {
                    this.checkChannelVisibility();
                });
            });

            this.createView('channel', 'export:views/export-feed/fields/channel', {
                model: this.model,
                name: 'channel',
                el: `${this.options.el} .field[data-name="channel"]`,
                mode: 'edit',
                params: {
                    required: true
                },
                labelText: this.translate('channel', 'scopeNames', 'Global'),
                createDisabled: true
            }, view => {});
        },

        getColumnFromAttribute() {
            let column = '';
            if (this.model.get('attributeId')) {
                column = this.model.get('attributeName');
                let channelName = this.model.get('channelName');
                if (this.model.get('scope') === 'Channel' && channelName) {
                    column += ` (Channel: ${channelName})`;
                } else {
                    column += ` (${this.model.get('scope')})`;
                }
            }
            return column;
        },

        checkChannelVisibility() {
            let channel = this.getView('channel');
            if (this.model.get('scope') === 'Channel') {
                channel.params.required = true;
                channel.show();
            } else {
                channel.params.required = false;
                channel.hide();
            }
            channel.reRender();
        },

        setAllowedFields() {},

        applyDynamicChanges() {
            this.checkChannelVisibility();
        },

        actionSave() {
            if (!this.validate()) {
                let data = {};
                let fields = this.nestedViews;
                for (let i in fields) {
                    let view = fields[i];
                    if (!view.disabled && !view.readOnly && view.isFullyRendered()) {
                        _.extend(data, view.fetch());
                    }
                }
                this.model.set(data, {silent: true});
                this.trigger('after:save', this.model);
                this.dialog.close();
            }
        },

        validate() {
            let notValid = false;
            let fields = this.nestedViews;
            for (let i in fields) {
                notValid = fields[i].validate() || notValid;
            }
            return notValid
        }

    })
);
