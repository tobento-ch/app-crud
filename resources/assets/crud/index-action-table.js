const indexActionTable = (function(window, document) {
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
    
    const crud = {
        saveField: function(event, el) {
            const formData = new FormData();
            const entityIdEl = el.closest('[data-entity-id]');
            
            if (!entityIdEl) {
                return;
            }
            
            const entityId = entityIdEl.getAttribute('data-entity-id');
            let updateUrl = document.body.getAttribute('data-update-url');
            updateUrl = updateUrl.slice(0, -1)+entityId;
            
            el.querySelectorAll('[name]').forEach(el => {
                if (el.getAttribute('type') === 'checkbox' || el.getAttribute('type') === 'radio') {
                    if (el.checked) {
                        formData.append(el.getAttribute('name'), el.value);
                    }
                } else {
                    formData.append(el.getAttribute('name'), el.value);
                }
            });
            
            formData.append('_method', 'PUT');
            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json"
                },
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.status !== 200) {
                    // Handle non-validation errors (403, 500, etc.)
                    if (!data.messages && data.message) {
                        let msgEl = el.querySelector('[data-field-error]');
                        if (!msgEl) {
                            msgEl = document.createElement('div');
                            msgEl.setAttribute('data-field-error', '');
                            msgEl.classList.add('form-message', 'error', 'mt-xs');
                            el.appendChild(msgEl);
                        }
                        msgEl.textContent = data.message;
                        return;
                    }
                    // Existing validation error handling
                    data.messages.forEach(message => {
                        if (message.key === null && el.querySelector('.form-message') === null) {
                            let msgEl = document.createElement('div');
                            msgEl.setAttribute('data-field-error', '');
                            msgEl.classList.add('form-message', 'error', 'mt-xs');
                            el.appendChild(msgEl);
                            msgEl.textContent = message.message;
                            return;
                        }
                        
                        const field = el.querySelector('[name^="'+toInputName(message.key)+'"]');
                        
                        if (field) {
                            let msgEl = el.querySelector('[data-field-error]');
                            
                            if (msgEl === null) {
                                msgEl = document.createElement('div');
                                msgEl.setAttribute('data-field-error', '');
                                msgEl.classList.add('form-message', 'error', 'mt-xs');
                                
                                if (field.getAttribute('type') === 'checkbox' || field.getAttribute('type') === 'radio') {
                                    field.parentNode.parentNode.appendChild(msgEl);
                                } else {
                                    field.parentNode.appendChild(msgEl);
                                }
                            }
                            
                            msgEl.textContent = message.message;
                        }
                    });
                } else {
                    const msgEl = el.querySelector('[data-field-error]');
                    
                    if (msgEl) {
                        msgEl.remove();
                    }
                    
                    // Add success indicator
                    let ok = el.querySelector('[data-field-success]');
                    if (!ok) {
                        ok = document.createElement('span');
                        ok.setAttribute('data-field-success', '');
                        ok.classList.add('badge', 'round', 'text-success', 'background-white', 'inline-success-indicator');
                        ok.textContent = '✔';
                        el.appendChild(ok);
                    }

                    // Auto-remove
                    setTimeout(() => ok.remove(), 1200);
                }
            });
        },
        registerTableEvents: function() {
            let globalTimeout = null;
            
            ['keyup', 'change'].forEach(evt => {
                document.addEventListener(evt, function(e) {
                    const el = event.target.closest('[data-field]');
                    
                    if (el === null) {
                        return;
                    }
                    
                    if (e.type === 'keyup') {
                        if (globalTimeout != null) {
                            clearTimeout(globalTimeout);
                        }
                        
                        globalTimeout = setTimeout(() => {
                            globalTimeout = null;
                            crud.saveField(e, el);
                        }, 200);
                    } else {
                        crud.saveField(e, el);
                    }
                });
            });
        }
    };
    
    document.addEventListener('DOMContentLoaded', (e) => {
        crud.registerTableEvents();
    });
    
    return crud;
    
})(window, document);

export default indexActionTable;