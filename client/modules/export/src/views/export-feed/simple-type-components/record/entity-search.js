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
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

Espo.define('export:views/export-feed/simple-type-components/record/entity-search', 'views/record/search',
    Dep => Dep.extend({

        disableSavePreset: true,

        setup() {
            Dep.prototype.setup.call(this);

            this.presetFilterList = [];
            this.boolFilterList = [];
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.afterRenderExportFilterPanel();
        },

        afterRenderExportFilterPanel() {
            this.$el.find('.search-row > .form-group').attr('class', 'form-group col-md-12');
            this.$el.find('.search[data-action="search"]').remove();

            this.setFilterMode();
            this.listenTo(this.options.feedModel, 'change:export-feed-mode', () => {
                this.setFilterMode();
            });

            this.listenTo(this.options.feedModel, 'save:export-feed', () => {
                let filterData = this.getFilterData() || {};
                this.options.feedModel.set('data', _.extend({}, this.options.feedModel.get('data'), filterData));
            });
        },

        getFilterData() {
            this.search();
            return {
                where: Espo.Utils.cloneDeep(this.searchManager.getWhere()),
                whereData: Espo.Utils.cloneDeep(this.searchManager.get()),
                whereScope: this.scope,
            }
        },

        isLeftDropdown() {
            return true;
        },

        resetFilters() {
            if (this.getParentView().getParentView().getParentView().mode !== 'edit') {
                return false;
            }

            return Dep.prototype.resetFilters.call(this);
        },

        refresh() {
            // leave empty
        },

        updateCollection() {
            // leave empty
        },

        setFilterMode() {
            let detailView = this.getDetailView();
            if (detailView && detailView.mode === 'edit') {
                this.$el.find('select, input, button, .selectize-input').removeClass('disabled').removeAttr('disabled');
                this.$el.find('.remove-filter, .remove-attribute-filter').show();
                $('.selectized').each(function () {
                    this.selectize.enable();
                });
            } else {
                this.$el.find('select, input, button, .selectize-input').addClass('disabled').attr('disabled', true);
                this.$el.find('.remove-filter, .remove-attribute-filter').hide();
                $('.selectized').each(function () {
                    this.selectize.disable();
                });
            }
        },

        getDetailView() {
            if (this.getParentView() && this.getParentView().getParentView() && this.getParentView().getParentView().getParentView()) {
                return this.getParentView().getParentView().getParentView();
            }

            return null;
        },

    })
);
