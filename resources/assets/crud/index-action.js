import modals from './../modal/modals.js';
import notifier from './../js-notifier/notifier.js';

const indexAction = (function(window, document) {
    'use strict';
    
    class BulkAction {
        constructor(el) {
            this.el = el;
            this.name = el.getAttribute('data-bulk-action');
            
            this.el.addEventListener('click', (e) => {
                modals.get(this.name).open();
            });
            
            document.addEventListener('click', (e) => {
                if (e.target.matches('[data-bulk-save="'+this.name+'"]')) {
                    this.handleSaveAction(e);
                }
            });
        }
        handleSaveAction(event) {
            let form = event.target.closest('form');
            
            if (!form) {
                form = document.querySelector('form[name="'+this.name+'"]');
            }
            
            const formData = new FormData(form);
            
            if (form.reportValidity() === false) {
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
                const disposition = response.headers.get('Content-Disposition');

                // If server returns a file download
                if (disposition && disposition.includes('attachment')) {
                    return response.blob().then(blob => {
                        // Extract filename from header
                        let filename = 'download';
                        const match = /filename="?([^"]+)"?/.exec(disposition);
                        if (match) {
                            filename = match[1];
                        }

                        // Trigger browser download
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(url);
                        return null; // signal: download happened
                    });
                }

                // Otherwise treat as HTML
                return response.text();
            }).then(result => {
                // If result === null → file download happened → stop AJAX flow
                if (result === null) {
                    targetEl.classList.remove('loading');
                    targetEl.removeAttribute('disabled');

                    modals.get(this.name).close();
                    form.reset();

                    crud.fire('bulk.saved', [event, this]);
                    return;
                }

                // Normal HTML response handling
                const string = result;
    
                const bulkNames = Array.from(
                    document.querySelectorAll('[data-bulk-ajax-refresh]')
                ).map(el => el.getAttribute('data-bulk-ajax-refresh'));
                
                const replaces = [
                    '[data-table-group="items"]',
                    ...bulkNames.map(name => '[data-bulk-ajax-refresh="'+name+'"]')
                ];

                const doc = (new DOMParser()).parseFromString(string, 'text/html');

                replaces.forEach(selector => {
                    const newEls = doc.querySelectorAll(selector);
                    const oldEls = document.querySelectorAll(selector);

                    // Replace elements one by one
                    newEls.forEach((newEl, index) => {
                        const oldEl = oldEls[index];
                        if (newEl && oldEl && oldEl.parentNode) {
                            oldEl.parentNode.replaceChild(newEl, oldEl);
                        }
                    });
                });

                targetEl.classList.remove('loading');
                targetEl.removeAttribute('disabled');
                
                const messages = doc.querySelectorAll('[data-message]');
                
                messages.forEach(el => {
                    notifier.send({
                        status: el.getAttribute('data-message'),
                        text: el.textContent
                    });
                });
                
                const formErrorEl = document.querySelector('.form-message.error');
                const isFullPage = string.includes('<html');
                
                // Detect whether the response is a full page or a modal partial.
                // In this flow only the final successful step returns a full page (contains <html>).
                // All intermediate steps (Step 1, Step 2 errors, and any future multi‑step screens)
                // return partial modal HTML without <html>. These should keep the modal open.
                // Therefore:
                // - Partial → still inside the multi‑step modal → keep it open
                if (formErrorEl || !isFullPage) {
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
            const dropdownEl = document.querySelector('[data-dropdown="bulk"]');
            
            document.addEventListener('click', (e) => {
                
                const el = e.target.closest('[name^="bulk"]');

                if (!dropdownEl) {
                    return;
                }

                if (el) {
                    const bulkRows = document.querySelectorAll('[name="bulk[]"]');
                    const hasRows = bulkRows.length > 0;
                    const count = document.querySelectorAll('[name="bulk[]"]:checked').length;
                    const isOpen = !dropdownEl.classList.contains('display-none');

                    if (!hasRows) {
                        // Toggle behavior when no rows exist
                        if (isOpen) {
                            dropdownEl.classList.add('display-none');
                            document.body.appendChild(dropdownEl);
                        } else {
                            dropdownEl.classList.remove('display-none');
                            el.parentNode.appendChild(dropdownEl);
                        }
                        return;
                    }

                    // Normal behavior when rows exist
                    if (count > 0 && !e.target.closest('.modal')) {
                        dropdownEl.classList.remove('display-none');
                        el.parentNode.appendChild(dropdownEl);
                    } else {
                        dropdownEl.classList.add('display-none');
                        document.body.appendChild(dropdownEl);
                    }
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

                // remove not found filters which:
                document.querySelectorAll('[data-filter]').forEach(el => {
                    const newEl = doc.querySelector('[data-filter="'+el.getAttribute('data-filter')+'"]');
                    
                    if (!newEl && !el.closest('[data-table-group]')) {
                        el.remove();
                    }
                });
                
                doc.querySelectorAll('[data-filter]').forEach(el => {
                    const value = el.getAttribute('data-filter');
                    // only update inactive filters as not to loose focus on active:
                    if (! el.querySelector('#'+activeFilterId)) {
                        const oldEl = document.querySelector('[data-filter="'+value+'"]');
                        if (oldEl) {
                            oldEl.parentNode.replaceChild(el, oldEl);
                        } else {
                            // add new filter element:
                            if (el.previousElementSibling) {
                                document.querySelector('[data-filter="'+el.previousElementSibling.getAttribute('data-filter')+'"]').after(el);
                            } else if (el.nextElementSibling) {
                                document.querySelector('[data-filter="'+el.nextElementSibling.getAttribute('data-filter')+'"]').before(el);
                            }
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