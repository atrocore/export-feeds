

Espo.define('export:views/export-feed/simple-type-components/record/product-search', 'pim:views/product/record/search',
    Dep => Dep.extend({

        disableSavePreset: true,

        setup() {
            Dep.prototype.setup.call(this);

            this.presetFilterList = [];
            this.boolFilterList = [];
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            const formGroup = this.$el.find('.search-row > .form-group');
            formGroup.attr('class', formGroup.attr('class').replace(/\bcol-[a-z]{2}-\d+\b/g, ''));
            formGroup.addClass('col-md-12');

            this.$el.find('.search[data-action="search"]').html(`<span class="fa fa-save"></span><span>${this.translate('Save')}</span>`);
        },

        isLeftDropdown() {
            return true;
        },

        refresh() {
            //leave empty
        },

        updateCollection() {
            this.trigger('saveProductsFilter')
        }

    })
);
