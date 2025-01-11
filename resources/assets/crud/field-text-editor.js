import editors from './../js-editor/core/editors.js';
import translator from './../js-editor/core/translator.js';
import table from './../js-editor/plugin/table.js';
import styles from './../js-editor/plugin/styles.js';
import { basic, html, clear, links } from './../js-editor/plugin/basic.js';
import button from './../crud/button.js';

// you may add translations:
//translator.locale('de-CH');
translator.locale(document.querySelector('html').getAttribute('lang'));
translator.localeFallbacks({"de-CH": "en"});
translator.add('de-CH', {
    "Table": "Tabelle",
    "Add row below": "Zeile danach einfügen",
    "Add row above": "Zeile davor einfügen",
    "Delete row": "Zeile löschen",
    "Add column left": "Spalte links einfügen",
    "Add column right": "Spalte rechts einfügen",
    "Delete column": "Spalte löschen",
    "Delete table": "Tabelle löschen"
});

// you may add styles:
/*styles.add({
    key: "style.fonts",
    title: "Font styles",
    options: {
        "Default Font": "",
        "Primary Font": "font-primary",
        "Secondary Font": "font-secondary"
    },
    optionToClass: true
});*/

// add plugins:
editors.plugins([basic, html, clear, links, table, styles]);

// reregister editors on dom updated
button.listen('dom.updated', (el) => {
    editors.items = {};
    editors.register();
});

const fieldTextEditor = (function(window, document) {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', (e) => {
        // init the editors:
        editors.init();

        // you may auto register editors with the data-editor attribute:
        editors.register();
    });
    
    return editors;
    
})(window, document);

export default fieldTextEditor;