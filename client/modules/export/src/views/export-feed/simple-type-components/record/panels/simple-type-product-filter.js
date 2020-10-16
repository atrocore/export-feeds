

/*
 * This file is part of premium software, which is NOT free.
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This Software is the property of AtroCore UG (haftungsbeschränkt) and is
 * protected by copyright law - it is NOT Freeware and can be used only in one
 * project under a proprietary license, which is delivered along with this program.
 * If not, see <https://atropim.com/eula> or <https://atrodam.com/eula>.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
 */

Espo.define('export:views/export-feed/simple-type-components/record/panels/simple-type-product-filter', ['views/record/panels/bottom', 'search-manager'],
    (Dep, SearchManager) => Dep.extend({

        template: 'export:export-feed/simple-type-components/record/panels/simple-type-product-filter',

        searchManager: null,

        collection: null,

        setup() {
            Dep.prototype.setup.call(this);

            this.scope = 'Product';

            this.wait(true);
            this.getCollectionFactory().create(this.scope, collection => {
                this.collection = collection;
                this.searchManager = new SearchManager(this.collection, 'exportProductSimpleType', null, this.getDateTime(), (this.model.get('data') || {}).whereData || [], true);
                this.setupSearchPanel(() => this.wait(false));
            });
        },

        setupSearchPanel(callback) {
            const hiddenBoolFilterList = this.getMetadata().get(`clientDefs.${this.scope}.hiddenBoolFilterList`) || [];
            const searchView = 'export:views/export-feed/simple-type-components/record/product-search';

            this.createView('search', searchView, {
                collection: this.collection,
                el: `${this.options.el} .search-container`,
                searchManager: this.searchManager,
                scope: this.scope,
                viewMode: 'list',
                hiddenBoolFilterList: hiddenBoolFilterList,
            }, view => {
                this.listenTo(view, 'saveProductsFilter', () => {
                    this.notify('Saving...');
                    let data = _.extend({}, this.model.get('data'), {
                        where: Espo.Utils.cloneDeep(this.searchManager.getWhere()),
                        whereData: Espo.Utils.cloneDeep(this.searchManager.get()),
                    });
                    this.model.set({data: data});
                    this.model.save(null, {
                        success: () => this.notify('Saved', 'success'),
                        error: () => this.notify(false)
                    });
                });

                callback();
            });
        },

    })
);
