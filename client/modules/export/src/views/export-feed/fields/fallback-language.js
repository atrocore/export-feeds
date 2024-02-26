/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

Espo.define('export:views/export-feed/fields/fallback-language', 'export:views/export-feed/fields/language',
    Dep => {

        return Dep.extend({

            prohibitedEmptyValue: false,

            setup(){
                Dep.prototype.setup.call(this);
                this.listenTo(this.model, 'change:language', () => {
                    let previousValue = this.model.get('fallbackLanguage')
                    Dep.prototype.setup.call(this);
                    this.model.set('fallbackLanguage', previousValue === this.model.get('language')
                        || this.model.get('language') === '' ? '' : previousValue
                    )
                    this.reRender();
                });
            },

            setupOptions() {

                if(this.model.get('language') === "main"){
                    this.params.options = [];
                    this.translatedOptions = {};
                }else{
                    this.params.options = ['main'];
                    this.translatedOptions = {"main": this.getLanguage().translateOption('main', 'languageFilter', 'Global')};
                }

                (this.getConfig().get('inputLanguageList') || []).forEach(locale => {
                    if(locale === this.model.get('language')) return;
                    this.params.options.push(locale);
                    this.translatedOptions[locale] = locale;
                });

            },

        })
    });