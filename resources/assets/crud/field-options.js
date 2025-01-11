const fieldOptions = (function(window, document) {
    'use strict';

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
                    actionEl.querySelector('input[type="checkbox"]').checked = true;
                    selectedEl.appendChild(actionEl);
                    break;
                case 'remove':
                    e.preventDefault();
                    actionEl.setAttribute('data-options-action', 'add');
                    actionEl.querySelector('input[type="checkbox"]').checked = false;
                    unselectedEl.prepend(actionEl);
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
            
            formData.append('search['+fieldName+']', e.target.value);

            const queryString = new URLSearchParams(formData).toString();
            let [uri, hash] = window.location.href.split("#");
            uri = uri+'?'+queryString;
            
            fetch(uri, {
                method: 'GET'
            }).then(response => {
                return response.text();
            }).then(string => {
                const selector = ['[data-unselected="'+fieldName+'"]'];
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