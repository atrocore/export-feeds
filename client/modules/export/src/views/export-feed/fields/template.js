/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

Espo.define('export:views/export-feed/fields/template', 'views/fields/script', Dep => {

    return Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.prepareJsonTemplate();
            this.listenTo(this.model, 'change:entity change:fileType', () => {
                this.prepareJsonTemplate();
            });
        },

        prepareJsonTemplate() {
            if (this.mode !== 'edit' || !this.model.isNew() || this.model.get('fileType') !== 'json') {
                return;
            }

            let templateData = [];
            $.each(this.getMetadata().get(['entityDefs', this.model.get('entity'), 'fields']), (fieldName, fieldDefs) => {
                if (fieldDefs.importDisabled || ['linkMultiple'].includes(fieldDefs.type)) {
                    return;
                }

                if (fieldName === 'id') {
                    templateData.push(`"${fieldName}": "{{ entity.${fieldName} }}"`);
                } else if (fieldDefs.type === 'bool') {
                    templateData.push(`"${fieldName}": {% if entity.${fieldName} %}true{% else %}false{% endif %}`);
                } else if (['int', 'float'].includes(fieldDefs.type)) {
                    templateData.push(`"${fieldName}": {% if entity.${fieldName} %}{{ entity.${fieldName} }}{% else %}null{% endif %}`);
                } else if (['varchar', 'enum', 'text', 'wysiwyg'].includes(fieldDefs.type)) {
                    templateData.push(`"${fieldName}": {% if entity.${fieldName} %}"{{ entity.${fieldName} | escapeStr | raw }}"{% else %}null{% endif %}`);
                } else if (['link', 'asset', 'file', 'image'].includes(fieldDefs.type)) {
                    templateData.push(`"${fieldName}Id": {% if entity.${fieldName} %}"{{ entity.${fieldName}Id }}"{% else %}null{% endif %}`);
                    templateData.push(`"${fieldName}Name": {% if entity.${fieldName} %}"{{ entity.${fieldName}Name | escapeStr | raw }}"{% else %}null{% endif %}`);
                } else if (['array', 'multiEnum'].includes(fieldDefs.type)) {
                    templateData.push(`"${fieldName}": {% if entity.${fieldName} %}{{ entity.${fieldName} | json_encode | raw }}{% else %}null{% endif %}`);
                }
            });

            this.model.set('template', '{% set siteUrl = config.siteUrl %}\n{% set entity = entities[0] %}\n{\n    ' + templateData.join(',\n    ') + '}');
        },

    })
});