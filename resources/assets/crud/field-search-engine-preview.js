const fieldSearchEnginePreview = (function(window, document) {
    'use strict';
    
    // converts "meta_title[de]" to "meta_title.de"
    const toDot = (name) => name.replaceAll('[', '.').replaceAll(']', '');
    
    // converts "meta_title.de" to "meta_title[de]"
    const toName = (path) => path.replace(/\.([^.]+)/g, '[$1]');
    
    const preview = {
        register: function() {
            // we add the event globally as not to loose listeners on update DOM
            document.addEventListener('input', (e) => {
                const name = e.target.getAttribute('name');
                
                if (name) {
                    document.querySelectorAll('[data-search-engine-preview]').forEach(el => preview.update(el, toDot(name)));
                }
            });
        },
        update: function(el, path) {
            const config = JSON.parse(el.getAttribute('data-search-engine-preview'));
            const sources = [...config.title, ...config.slug, ...config.description];
            let locale = null;
            
            for (const source of sources) {
                // not translatable, keep the locale shown:
                if (!source.includes('{locale}')) {
                    if (source === path) {
                        locale = el.getAttribute('data-current-locale') || config.locale;
                        break;
                    }
                    continue;
                }
                
                // translatable, switch to the locale of the input being edited:
                const match = Object.keys(config.locales).find(locale => source.replace('{locale}', locale) === path);
                
                if (match) {
                    locale = match;
                    break;
                }
            }
            
            if (!locale) {
                return;
            }
            
            el.setAttribute('data-current-locale', locale);
            
            const value = (sources) => {
                for (const source of sources) {
                    const input = el.closest('form').querySelector('[name="'+toName(source.replace('{locale}', locale))+'"]');
                    
                    // the value may be html, e.g. from a text editor. A template is inert:
                    const template = document.createElement('template');
                    template.innerHTML = input ? input.value : '';
                    const text = template.content.textContent.trim();
                    
                    if (text !== '') {
                        return text;
                    }
                }
                
                return '';
            };
            
            const set = (name, text) => {
                el.querySelector('[data-search-engine-preview-'+name+']').textContent = text;
            };
            
            set('title', value(config.title));
            set('url', config.uri.replaceAll('{locale}', locale).replaceAll('{slug}', value(config.slug)));
            set('description', value(config.description));
            
            if (config.locales[locale]) {
                set('locale', config.locales[locale]);
            }
        }
    };
    
    document.addEventListener('DOMContentLoaded', (e) => {
        preview.register();
    });
    
    return preview;

})(window, document);

export default fieldSearchEnginePreview;