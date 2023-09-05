/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.md, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

Espo.define('export:views/export-feed/fields/language', 'views/fields/enum',
    Dep => {

        return Dep.extend({

            prohibitedEmptyValue: false,

            setupOptions() {
                this.params.options = ['main'];
                this.translatedOptions = {"main": this.getLanguage().translateOption('main', 'languageFilter', 'Global')};

                (this.getConfig().get('inputLanguageList') || []).forEach(locale => {
                    this.params.options.push(locale);
                    this.translatedOptions[locale] = locale;
                });
            },

        })
    });