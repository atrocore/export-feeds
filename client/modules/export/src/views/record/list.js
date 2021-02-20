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

Espo.define('export:views/record/list', 'views/record/list',
    Dep => Dep.extend({

        collectionFetchInterval: null,

        setup() {
            this.rowActionsView = this.options.rowActionsView || this.getMetadata().get(['clientDefs', this.options.scope || this.collection.name || null, 'rowActionsView']) || this.rowActionsView;

            Dep.prototype.setup.call(this);
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.setCollectionFetchInterval();
        },

        setCollectionFetchInterval() {
            let collectionFetchInterval = this.getMetadata().get(['clientDefs', this.scope, 'collectionFetchInterval']);
            if (collectionFetchInterval && !this.collectionFetchInterval) {
                this.collectionFetchInterval = window.setInterval(() => this.collection.fetch(), collectionFetchInterval);
                this.listenToOnce(this.getRouter(), 'routed', () => {
                    this.collectionFetchInterval && window.clearInterval(this.collectionFetchInterval);
                    this.collectionFetchInterval = null;
                });
            }
        }

    })
);