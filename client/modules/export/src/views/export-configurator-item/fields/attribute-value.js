/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

Espo.define('export:views/export-configurator-item/fields/attribute-value', 'views/fields/enum', Dep => Dep.extend({

    setup() {
        Dep.prototype.setup.call(this);

        this.listenTo(this.model, 'change:name change:type change:attributeId', () => {
            this.setupOptions();
            this.reRender();
            this.model.set('attributeValue', this.params.options[0] ?? null);
        });
    },

    afterRender() {
        Dep.prototype.afterRender.call(this);

        if (this.mode !== 'list') {
            this.checkFieldVisibility();
        }
    },

    checkFieldVisibility() {
        if (this.isRequired()) {
            this.show();
        } else {
            this.hide();
        }
    },

    setupOptions() {
        this.params.options = [];
        this.translatedOptions = {};

        if (this.isRequired()) {
            const type = this.getType();
            this.params.options = ['value'];
            if (['rangeFloat', 'rangeInt'].includes(type)) {
                this.params.options.push('valueFrom', 'valueTo')
                if (this.hasUnit()) {
                    this.params.options.push('valueUnit')
                }
            } else if (['float', 'int'].includes(type)) {
                if (this.hasUnit()) {
                    this.params.options.push('valueNumeric', 'valueUnit')
                } else {
                    this.params.options = ['valueNumeric']
                }
            } else if (type === 'varchar') {
                if (this.hasUnit()) {
                    this.params.options.push('valueString', 'valueUnit')
                } else {
                    this.params.options = ['valueString']
                }
            }
            this.params.options.push('id')

            this.params.options.forEach(option => {
                this.translatedOptions[option] = this.getLanguage().translateOption(option, 'attributeValue', 'ExportConfiguratorItem');
            });
        }
    },

    getType() {
        if (this.model.get('attributeId')) {
            return this.getAttribute(this.model.get('attributeId')).type;
        }
        return 'varchar';
    },
    isRequired() {
        return this.model.get('type') === 'Attribute' && this.model.get('attributeId');
    },
    hasUnit() {
        if (this.model.get('attributeId')) {
            const attribute = this.getAttribute(this.model.get('attributeId'));
            if (attribute.measureId) {
                return true
            }
        }
        return false
    },
    getAttribute(attributeId) {
        let key = `attribute_${attributeId}`;
        if (!Espo[key]) {
            Espo[key] = null;
            this.ajaxGetRequest(`Attribute/${this.model.get('attributeId')}`, null, {async: false}).success(attr => {
                Espo[key] = attr;
            });
        }

        return Espo[key];
    },

}));