const fieldOptions = (function(window, document) {
    'use strict';
    
    function toInputName(string) {
        const segments = string.split('.');
        let name = segments[0];

        delete segments[0];

        segments.forEach(segment => {
            name += '['+segment+']';
        });

        return name;
    }
    
    const options = {
        register: function() {
            // we add click event globally as not to loose listeners on update DOM
            document.addEventListener('click', (e) => {
                const actionEl = e.target.closest('[data-options-action]');

                if (actionEl) {
                    options.handleClickAction(e, actionEl);
                }
            });
            
            let globalTimeout = null;
            
            document.addEventListener('keyup', (e) => {
                const actionEl = e.target.closest('[data-options-action]');

                if (actionEl && actionEl.getAttribute('data-options-action') === 'search') {
                    if (globalTimeout != null) {
                        clearTimeout(globalTimeout);
                    }

                    globalTimeout = setTimeout(() => {
                        globalTimeout = null;
                        options.search(e, actionEl);
                    }, 200);
                }
            });
        },
        handleClickAction: function(e, actionEl) {
            const optionsEl = e.target.closest('[data-options]');
            
            if (!optionsEl) {
                return;
            }
            
            const selectedEl = optionsEl.querySelector('[data-selected]');
            const unselectedEl = optionsEl.querySelector('[data-unselected]');
            
            switch (actionEl.getAttribute('data-options-action')) {
                case 'add':
                    e.preventDefault();
                    actionEl.setAttribute('data-options-action', 'remove');
                    setTimeout(() => {
                        const inpEl = actionEl.querySelector('input[type="checkbox"]');
                        inpEl.checked = true;
                        // dispatch event for live.js
                        inpEl.dispatchEvent(new Event('change', { bubbles: true }));
                        selectedEl.appendChild(actionEl);
                    }, 10);
                    break;
                case 'remove':
                    e.preventDefault();
                    actionEl.setAttribute('data-options-action', 'add');
                    setTimeout(() => {
                        const inpEl = actionEl.querySelector('input[type="checkbox"]');
                        inpEl.checked = false;
                        // dispatch event for live.js
                        inpEl.dispatchEvent(new Event('change', { bubbles: true }));
                        unselectedEl.prepend(actionEl);
                    }, 10);
                    break;
            }
        },
        search: function(e, actionEl) {
            const optionsEl = e.target.closest('[data-options]');
            
            if (!optionsEl) {
                return;
            }
            
            e.preventDefault();
            const formData = new FormData();
            const fieldName = optionsEl.getAttribute('data-options');
            
            formData.append(toInputName('options-search.'+fieldName), e.target.value);
            
            const queryString = new URLSearchParams(formData).toString();
            let [uri, hash] = window.location.href.split("#");
            let [baseUrl, existingQuery] = uri.split("?");
            
            const mergedParams = new URLSearchParams(existingQuery);
            for (const [key, value] of new URLSearchParams(queryString)) {
                mergedParams.set(key, value);
            }
            
            uri = baseUrl+'?'+mergedParams.toString();
            
            fetch(uri, {
                method: 'GET'
            }).then(response => {
                return response.text();
            }).then(string => {
                const selector = '[data-unselected="'+fieldName+'"]';
                const doc = (new DOMParser()).parseFromString(string, 'text/html');
                const newEl = doc.querySelector(selector);
                const oldEl = document.querySelector(selector);
                
                if (newEl && oldEl) {
                    oldEl.parentNode.replaceChild(newEl, oldEl);
                    options.removeDublicate(optionsEl);
                }
            });
        },
        removeDublicate: function(optionsEl) {
            const selectedEl = optionsEl.querySelector('[data-selected]');
            const unselectedEl = optionsEl.querySelector('[data-unselected]');
            const selectedValues = [];
            
            selectedEl.querySelectorAll('input').forEach(el => {
                selectedValues.push(el.value);
            });
            
            unselectedEl.querySelectorAll('input').forEach(el => {
                if (selectedValues.indexOf(el.value) !== -1) {
                    el.closest('[data-options-action]').remove();
                }
            });
        }
    };
    
    document.addEventListener('DOMContentLoaded', (e) => {
        options.register();
    });
    
    return options;
    
})(window, document);

export default fieldOptions;