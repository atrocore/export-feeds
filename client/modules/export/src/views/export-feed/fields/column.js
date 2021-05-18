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

Espo.define('export:views/export-feed/fields/column', 'views/fields/base', function (Dep) {

    return Dep.extend({

        getCellElement: function () {
            return this.$el;
        },

        init: function () {
            Dep.prototype.init.call(this);

            if (this.model.get('column') === '...') {
                this.inlineEditDisabled = true;
            }

            this.listenTo(this.model, 'change:field change:exportIntoSeparateColumns change:columnType', () => {
                this.reRender();
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'list' && !this.inlineEditDisabled) {
                this.listenTo(this, 'after:render', this.initInlineEdit, this);
            }

            if (this.mode === 'edit' || this.mode === 'detail') {
                if (this.params.listView || !this.isCustomType()) {
                    this.checkFieldDisability();
                } else {
                    this.checkFieldVisibility();
                }
            }

            this.prepareValue();
        },

        checkFieldDisability() {
            if (this.isPavs() || !this.isCustomType()) {
                this.$el.find('input').attr('disabled', 'disabled');
            } else {
                this.$el.find('input').removeAttr('disabled');
            }
        },

        checkFieldVisibility() {
            if (this.isPavs()) {
                this.model.set('column', '...');
                this.$el.hide();
            } else {
                this.$el.show();
            }
        },

        isCustomType() {
            return this.model.get('columnType') === 'custom';
        },

        prepareValue() {
            if (!this.model.get('columnType') || this.model.get('columnType') === 'name') {
                let locale = this.getMetadata().get(`entityDefs.${this.model.get('entity')}.fields.${this.model.get('field')}.multilangLocale`);
                if (locale) {
                    let translates = this.options.translates || this.params.translates;
                    let originField = this.getMetadata().get(`entityDefs.${this.model.get('entity')}.fields.${this.model.get('field')}.multilangField`);
                    let columnName = originField;

                    if (translates[locale][this.model.get('entity')] && translates[locale][this.model.get('entity')]['fields'][originField]) {
                        columnName = translates[locale][this.model.get('entity')]['fields'][originField];
                    } else if (translates[locale]['Global'] && translates[locale]['Global']['fields'][originField]) {
                        columnName = translates[locale]['Global']['fields'][originField];
                    }

                    this.model.set('column', columnName);
                } else {
                    this.model.set('column', this.translate(this.model.get('field'), 'fields', this.model.get('entity')));
                }
            }

            if (this.model.get('columnType') === 'internal') {
                this.model.set('column', this.translate(this.model.get('field'), 'fields', this.model.get('entity')));
            }
        },

        isRequired() {
            return true;
        },

        isPavs() {
            return this.model.get('entity') === 'Product' && this.model.get('field') === 'productAttributeValues' && this.model.get('exportIntoSeparateColumns');
        },

        initInlineEdit: function () {
            let $cell = this.getCellElement();
            let $editLink = $('<a href="javascript:" class="pull-right inline-edit-link hidden"><span class="fas fa-pencil-alt fa-sm"></span></a>');

            if ($cell.size() === 0) {
                this.listenTo(this, 'after:render', this.initInlineEdit, this);
                return;
            }
            $cell.css({'position': "relative", 'paddingRight': "12px"});
            $editLink.css({'position': 'absolute', 'right': '0'});

            $cell.prepend($editLink);

            $editLink.on('click', function () {
                this.inlineEdit();
            }.bind(this));

            $cell.on('mouseenter', function (e) {
                e.stopPropagation();
                if (this.disabled || this.readOnly) {
                    return;
                }
                if (this.mode === 'detail' || this.mode === 'list') {
                    $editLink.removeClass('hidden');
                }
            }.bind(this)).on('mouseleave', function (e) {
                e.stopPropagation();
                if (this.mode === 'detail' || this.mode === 'list') {
                    $editLink.addClass('hidden');
                }
            }.bind(this));
        },

        addInlineEditLinks: function () {
            let $cell = this.$el;
            let $saveLink = $('<a href="javascript:" class="pull-right inline-save-link">' + this.translate('Update') + '</a>');
            let $cancelLink = $('<a href="javascript:" class="pull-right inline-cancel-link">' + this.translate('Cancel') + '</a>');
            $cell.prepend($saveLink);
            $cell.prepend($cancelLink);
            $cell.find('.inline-edit-link').addClass('hidden');
            $saveLink.click(function (e) {
                e.stopPropagation();
                this.inlineEditSave();
            }.bind(this));
            $cancelLink.click(function () {
                this.inlineEditClose();
            }.bind(this));
        },

        inlineEditSave: function () {
            let data = this.fetch();
            let prev = this.initialAttributes;
            this.model.set(data, {silent: true});

            if (this.validate()) {
                this.notify('Not valid', 'error');
                this.model.set(prev, {silent: true});
                return;
            }
            this.model.trigger('updateColumn');
            this.inlineEditClose(true);
        },

        validateRequired: function () {
            if (this.isRequired()) {
                if (this.model.get(this.name) === '') {
                    let msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', 'ExportFeed'));
                    this.showValidationMessage(msg);
                    return true;
                }
            }
        },

        validate: function () {
            let configuration = {};

            if (this.params.configurator) {
                configuration = this.params.configurator.configuration;
            }

            if (this.options.configurator) {
                configuration = this.options.configurator.configuration;
            }

            let value = this.model.get(this.name);

            let isNotValid = false;
            $.each(configuration, function (k, item) {
                if (this.model.id !== (k + 1)) {
                    if (_.isEqual(item.column, value)) {
                        if (value === '...') {
                            Espo.Ui.notify(this.translate('columnAlreadyExist', 'messages', 'ExportFeed'), 'error', 1000 * 60 * 60 * 2, true);
                        } else {
                            this.showValidationMessage(this.translate('columnAlreadyExist', 'messages', 'ExportFeed'));
                        }
                        isNotValid = true;
                    }

                    if (typeof item.exportIntoSeparateColumns !== 'undefined' && item.exportIntoSeparateColumns) {
                        if (value.match(new RegExp(`^${item.column} [0-9]+$`, 'gm'))) {
                            this.showValidationMessage(this.translate('columnAlreadyExist', 'messages', 'ExportFeed'));
                            isNotValid = true;
                        }
                    }
                }
            }.bind(this));

            return isNotValid;
        },

    })
});