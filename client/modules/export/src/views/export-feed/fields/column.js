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

            if (this.mode === 'list' || this.mode === 'detail') {
                if (!this.inlineEditDisabled) {
                    this.listenTo(this, 'after:render', this.initInlineEdit, this);
                }
            }
        },

        initInlineEdit: function () {
            let $cell = this.getCellElement();
            let $editLink = $('<a href="javascript:" class="pull-right inline-edit-link hidden"><span class="fas fa-pencil-alt fa-sm"></span></a>');

            if ($cell.size() === 0) {
                this.listenTo(this, 'after:render', this.initInlineEdit, this);
                return;
            }
            $cell.css({'position': "relative", 'paddingRight': "12px"});
            $editLink.css({'position':'absolute', 'right':'0'});

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
    })
});