import modals from './../modal/modals.js';

const indexAction = (function(window, document) {
    'use strict';
    
    class BulkAction {
        constructor(el) {
            this.el = el;
            this.name = el.getAttribute('data-bulk-action');
            
            this.el.addEventListener('click', (e) => {
                modals.get(this.name).open();
            });
            
            const saveEl = document.querySelector('[data-bulk-save="'+this.name+'"]');

            if (saveEl) {
                saveEl.addEventListener('click', (e) => {
                    this.handleSaveAction(e);
                });                
            }
        }
        handleSaveAction(event) {
            const form = event.target.closest('form');
            const formData = new FormData(form);
            
            if (form.checkValidity() === false) {
                return;
            }
            
            event.preventDefault();
            
            const targetEl = event.target;
            const selectedBulks = [];
            targetEl.classList.add('loading');
            targetEl.setAttribute('disabled', 'disabled');
            
            document.querySelectorAll('input[name^="bulk"]').forEach(el => {
                if (el.checked === true) {
                    formData.append('ids[]', el.value);
                    selectedBulks.push(el.value);
                }
            });

            fetch(form.getAttribute('action'), {
                method: form.getAttribute('method'),
                body: formData,
            }).then(response => {
                return response.text();
            }).then(string => {
                const replaces = ['[data-table-group="items"]', '[data-bulk-ajax-refresh]'];
                const doc = (new DOMParser()).parseFromString(string, 'text/html');

                replaces.forEach(selector => {
                    const newEl = doc.querySelector(selector);
                    const oldEl = document.querySelector(selector);
                    if (newEl && oldEl) {
                        oldEl.parentNode.replaceChild(newEl, oldEl);
                    }
                });

                targetEl.classList.remove('loading');
                targetEl.removeAttribute('disabled');
                
                const formErrorEl = document.querySelector('.form-message.error');

                if (formErrorEl) {
                    document.querySelectorAll('input[name^="bulk"]').forEach(el => {
                        if (selectedBulks.includes(el.value)) {
                            el.checked = true;
                        }
                    });
                } else {
                    modals.get(this.name).close();
                    form.reset();                    
                }
                
                crud.fire('bulk.saved', [event, this]);
            });
        }
    }

    const crud = {
        listeners: {},
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
        },
        registerBulks: function() {
            const bulksEl = document.querySelector('input[name="bulks"]');
            
            if (!bulksEl) {
                return;
            }
            
            // (Un)select bulks input:
            bulksEl.addEventListener('click', (e) => {
                document.querySelectorAll('input[name^="bulk"]').forEach(el => {
                    if (e.target.checked === true) {
                        el.checked = true;
                    } else {
                        el.checked = false;
                    }
                });
            });
            
            // Show dropdown menu on input click:
            document.addEventListener('click', (e) => {
                const el = event.target.closest('[name^="bulk"]');
                const dropdownEl = document.querySelector('[data-dropdown="bulk"]');
                
                if (!dropdownEl) {
                    return;
                }
                
                if (el) {
                    const count = document.querySelectorAll('[name="bulk[]"]:checked').length;
                    if (count > 0) {
                        dropdownEl.classList.remove('display-none');
                        el.parentNode.appendChild(dropdownEl);
                    } else {
                        dropdownEl.classList.add('display-none');
                    }
                } else if (! e.target.closest('.crud-dropdown')) {
                    dropdownEl.classList.add('display-none');
                    document.body.appendChild(dropdownEl);
                }
            });
            
            // Bulk Action:
            document.querySelectorAll('[data-bulk-action]').forEach(el => {
                const bulk = new BulkAction(el);
            });
        },
        registerFilters: function() {
            // register events globally as not to loose listeners on update filter DOM.
            ['keyup', 'change'].forEach(evt => {
                document.addEventListener(evt, function(e) {
                    
                    const form = e.target.closest('form');
                    
                    if (form && form.hasAttribute('data-form-filter')) {
                        const queryString = new URLSearchParams(new FormData(form)).toString();
                        const uri = form.getAttribute('action')+'?'+queryString;
                        
                        if (e.type === 'keyup') {
                            setTimeout(() => {
                                crud.updateFilter(uri, e);
                            }, 200);
                        } else {
                            crud.updateFilter(uri, e);
                        }
                    }

                    return;
                });
            });
            
            document.addEventListener('click', function(e) {
                if (! e.target.hasAttribute('href') || ! e.target.closest('[data-filter]')) {
                    return;
                }
                e.preventDefault();
                crud.updateFilter(e.target.getAttribute('href'), e);
            });
        },
        updateFilter: function(uri, event) {
            const activeFilterId = event.target.getAttribute('id');
            
            fetch(uri, {
                method: 'GET',
            }).then(response => {
                return response.text();
            }).then(string => {
                const doc = (new DOMParser()).parseFromString(string, 'text/html');
                const filterGroupEl = event.target.closest('[data-filters]');
                let replaces = [
                    '[data-table-group="heading"]', '[data-table-group="filters"]',
                    '[data-table-group="items"]'
                ];
                                
                // if input comes from a table field filter, we do not update all filter
                // as we would loose the active filter. All other filter groups we update
                // the whole table for the columns filter.
                if (filterGroupEl && filterGroupEl.getAttribute('data-filters') === 'field') {
                    replaces = replaces.filter(v => v !== '[data-table-group="filters"]');
                }
                
                replaces.forEach(selector => {
                    const newEl = doc.querySelector(selector);
                    const oldEl = document.querySelector(selector);
                    if (oldEl) {
                        oldEl.parentNode.replaceChild(newEl, oldEl);
                    }
                });
                
                doc.querySelectorAll('[data-filter]').forEach(el => {
                    const value = el.getAttribute('data-filter');
                    // only update inactive filters as not to loose focus on active:
                    if (! el.querySelector('#'+activeFilterId)) {
                        const oldEl = document.querySelector('[data-filter="'+value+'"]');
                        if (oldEl) {
                            oldEl.parentNode.replaceChild(el, oldEl);
                        }
                    }
                });
                
                doc.querySelectorAll('[data-update]').forEach(el => {
                    const value = el.getAttribute('data-update');
                    if (! el.querySelector('#'+activeFilterId)) {
                        const oldEl = document.querySelector('[data-update="'+value+'"]');
                        if (oldEl) {
                            oldEl.parentNode.replaceChild(el, oldEl);
                        }
                    }
                });
                
                crud.registerBulks();
                crud.fire('filter.updated', [event]);
            });
        },
        modalFilter: function() {
            // we add click event globally as not to loose listener on update DOM
            document.addEventListener('click', (e) => {
                const el = e.target.closest('[data-filter="modal-button"]');

                if (el && modals.has('filters')) {
                    modals.get('filters').open();
                }
            });
        }
    };
    
    document.addEventListener('DOMContentLoaded', (e) => {
        crud.registerBulks();
        crud.registerFilters();
        crud.modalFilter();
    });
    
    return crud;
    
})(window, document);

export default indexAction;