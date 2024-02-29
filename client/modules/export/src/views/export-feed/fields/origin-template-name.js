/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

Espo.define('export:views/export-feed/fields/origin-template-name', 'views/fields/enum', Dep => {

    return Dep.extend({

        setupOptions() {
            this.params.options = [''];
            this.translatedOptions = {'': ''};

            let data = this.getMetadata().get(['app', 'twigTemplates']) || {};

            Object.keys(data).forEach(key => {
                if ('entity' in data[key] && data[key].entity === this.model.get('entity')) {
                    this.params.options.push(key);
                    this.translatedOptions[key] = key;
                }
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.listenTo(this.model, 'change:' + this.name, () => {
                this.model.set('originTemplate', null);
                this.model.set('template', null);
                this.model.set('isTemplateEditable', false);

                let templateId = this.model.get(this.name);

                if (templateId) {
                    this.model.set('template', '{% extends "' + templateId + '" %}');

                    this.notify('Loading...');
                    this.ajaxGetRequest('ExportFeed/action/getOriginTemplate', {template: templateId}).success(res => {
                        if (res.template) {
                            this.model.set('originTemplate', res.template);
                        }

                        this.notify(false);
                    });
                }
            });

            if ((this.params.options || []).length) {
                this.show();
            } else {
                this.hide();
            }
        }
    })
});
