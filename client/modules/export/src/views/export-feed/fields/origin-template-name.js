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
        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:type change:fileType change:entity', () => this.setupOptions());

            this.listenTo(this.model, 'change:' + this.name, () => {
                this.model.set('originTemplate', null);
                this.model.set('template', null);
                this.model.set('isTemplateEditable', false);

                let templateId = this.model.get(this.name);

                if (templateId) {
                    let templateName = this.translatedOptions[templateId];

                    this.model.set('template', '{% extends "' + templateName + '" %}');

                    this.notify('Loading...');
                    this.loadOriginalTemplate(templateId, () => {
                        this.notify(false);
                    });
                }
            });

            if (this.model.get(this.name)) {
                this.loadOriginalTemplate(this.model.get(this.name))
            }
        },

        setupOptions() {
            this.params.options = [''];
            this.translatedOptions = {'': ''};

            this.loadAvailableTemplates();
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if ((this.params.options || []).length) {
                this.show();
            } else {
                this.hide();
            }
        },

        loadOriginalTemplate(template, callback) {
            this.ajaxGetRequest('ExportFeed/action/getOriginTemplate', {template: template}).success(res => {
                if (res.template) {
                    this.model.set('originTemplate', res.template);
                }

                if (callback) {
                    callback();
                }
            });
        },

        loadAvailableTemplates() {
            this.ajaxPostRequest('ExportFeed/action/loadAvailableTemplates', this.model.attributes).then(result => {
                if (result) {
                    Object.keys(result).forEach(template => {
                        this.params.options.push(template);
                        this.translatedOptions[template] = result[template];
                    });
                }
            });
        }
    })
});
