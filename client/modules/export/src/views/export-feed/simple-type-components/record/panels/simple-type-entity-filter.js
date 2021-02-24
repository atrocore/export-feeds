/*
 * Export Feeds
 * Free Extension
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

Espo.define('export:views/export-feed/simple-type-components/record/panels/simple-type-entity-filter', ['views/record/panels/bottom', 'search-manager'],
    (Dep, SearchManager) => Dep.extend({

        template: 'export:export-feed/simple-type-components/record/panels/simple-type-entity-filter',

        searchManager: null,

        collection: null,

        setup() {
            Dep.prototype.setup.call(this);

            this.scope = this.model.get('data').entity;
            this.wait(true);
            this.getCollectionFactory().create(this.scope, collection => {
                this.collection = collection;
                this.searchManager = new SearchManager(this.collection, `export${this.scope}SimpleType`, null, this.getDateTime(), (this.model.get('data') || {}).whereData || [], true);
                this.setupSearchPanel(() => this.wait(false));
            });

            this.listenTo(this.model, 'configuration-entity-changed', function (entity) {
                this.scope = entity;

                console.log(this.scope)

                this.getView('search').reRender();
            });
        },

        setupSearchPanel(callback) {
            const hiddenBoolFilterList = this.getMetadata().get(`clientDefs.${this.scope}.hiddenBoolFilterList`) || [];

            let searchView = 'export:views/export-feed/simple-type-components/record/entity-search';
            if (this.scope === 'Product') {
                searchView = 'export:views/export-feed/simple-type-components/record/product-search';
            }

            this.createView('search', searchView, {
                collection: this.collection,
                el: `${this.options.el} .search-container`,
                searchManager: this.searchManager,
                scope: this.scope,
                viewMode: 'list',
                hiddenBoolFilterList: hiddenBoolFilterList,
            }, view => {
                this.listenTo(view, 'saveEntityFilter', () => {
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
