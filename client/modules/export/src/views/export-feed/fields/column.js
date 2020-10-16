

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