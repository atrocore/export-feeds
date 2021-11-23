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

            this.scope = this.model.get('entity');

            this.setupSearchPanel();

            this.listenTo(this.model, 'change:entity', function () {
                this.scope = this.model.get('entity');

                let data = _.extend({}, this.model.get('data'));
                if (typeof data.whereScope === 'undefined' || data.whereScope !== this.scope) {
                    data = _.extend(data, {
                        where: null,
                        whereData: null,
                        whereScope: this.scope,
                    });
                    this.model.set({data: data});
                }
                this.setupSearchPanel();
            });
        },

        setupSearchPanel() {
            this.wait(true);
            this.getCollectionFactory().create(this.scope, collection => {
                this.collection = collection;
                this.searchManager = new SearchManager(this.collection, `exportSimpleType`, null, this.getDateTime(), (this.model.get('data') || {}).whereData || [], true);

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
                    hiddenBoolFilterList: this.getMetadata().get(`clientDefs.${this.scope}.hiddenBoolFilterList`) || [],
                    feedModel: this.model,
                }, view => {
                    view.render();
                    this.wait(false);
                });
            });
        },

    })
);
