import modals from './../modal/modals.js';
import button from './../crud/button.js';

const fieldSingleOptions = (function(window, document) {
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
        listeners: {},
        register: function() {
            // we add click event globally as not to loose listeners on update DOM
            document.addEventListener('click', (e) => {
                const actionEl = e.target.closest('[data-single-options-action]');

                if (actionEl) {
                    options.handleClickAction(e, actionEl);
                }
            });
            
            let globalTimeout = null;
            
            document.addEventListener('keyup', (e) => {
                const actionEl = e.target.closest('[data-single-options-action]');

                if (actionEl && actionEl.getAttribute('data-single-options-action') === 'search') {
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
            
            const displayModal = optionsEl.getAttribute('data-display-modal');
            const selectedEl = optionsEl.querySelector('[data-selected]');
            const unselectedEl = optionsEl.querySelector('[data-unselected]');
            const dropdownEl = optionsEl.querySelector('[data-dropdown]');
            const fieldEl = e.target.closest('[data-field]');
            
            switch (actionEl.getAttribute('data-single-options-action')) {
                case 'open-dropdown':
                    if (displayModal) {
                        modals.get(fieldEl.getAttribute('data-field')).open();
                        optionsEl.querySelector('[data-single-options-action="search"]').focus();
                        return;
                    }
                    
                    const closeDropdown = (e) => {
                        if (!e.target.closest('.crud-select-input-ctn')) {
                            dropdownEl.classList.remove('active');
                            document.removeEventListener('click', closeDropdown);
                        }
                    }
                    
                    dropdownEl.classList.toggle('active');
                    optionsEl.querySelector('[data-single-options-action="search"]').focus();
                    document.addEventListener('click', closeDropdown);
                    break;
                case 'add':
                    const fieldInputEl = optionsEl.querySelector('[data-field-input]');
                    fieldInputEl.value = actionEl.getAttribute('data-value');
                    
                    // dispatch event for live.js
                    fieldInputEl.dispatchEvent(new Event('change', { bubbles: true }));
                    
                    const selected = actionEl.cloneNode(true);
                    selected.removeAttribute('data-single-options-action');
                    selectedEl.innerHTML = selected.outerHTML;
                    
                    if (displayModal) {
                        modals.get(fieldEl.getAttribute('data-field')).close();
                    } else {
                        dropdownEl.classList.remove('active');
                    }
                    options.fire('option.added', [selectedEl, actionEl, optionsEl]);
                    break;
                case 'remove':
                    const fiEl = optionsEl.querySelector('[data-field-input]');
                    fiEl.value = '';
                    selectedEl.innerHTML = '';
                    options.fire('option.removed', [selectedEl, actionEl, optionsEl]);
                    
                    // dispatch event for live.js
                    fiEl.dispatchEvent(new Event('change', { bubbles: true }));
                    
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
            const selectedValue = optionsEl.querySelector('[data-field-input]').value;
            const unselectedEl = optionsEl.querySelector('[data-unselected]');

            unselectedEl.querySelectorAll('[data-single-options-action="add"]').forEach(el => {
                if (el.getAttribute('data-value') === selectedValue) {
                    el.closest('[data-single-options-action]').remove();
                }
            });
        },
        listen: function(eventName, callback) {
            if (typeof this.listeners[eventName] === 'undefined') {
                this.listeners[eventName] = [];
            }
            
            this.listeners[eventName].push(callback);
        },
        fire: function(eventName, parameters) {
            if (typeof this.listeners[eventName] === 'object') {
                this.listeners[eventName].forEach(listener => {
                    if (typeof listener === 'function') {
                        if (parameters instanceof Array) {
                            listener(...parameters);
                        } else if (parameters instanceof Object) {
                            listener(parameters);
                        }
                    }
                });
            }
        }
    };
    
    document.addEventListener('DOMContentLoaded', (e) => {
        options.register();
        
        button.listen('dom.updated', () => {
            document.querySelectorAll('[data-field-type="single-options"]').forEach(el => {
                delete modals.items[el.getAttribute('data-field')];
            });

            modals.register();
        });
    });
    
    return options;
    
})(window, document);

export default fieldSingleOptions;