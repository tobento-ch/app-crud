const live = (function(window, document) {
    'use strict';

    function serializeFormToNestedObject(form, changedElement = null, changedValue = null) {
        const result = {};
        const fd = new FormData(form);

        // Helper: convert "foo[1][bar]" → "foo.1.bar"
        const toDot = (name) =>
            name.replaceAll('[]', '').replaceAll('[', '.').replaceAll(']', '');

        // Helper: assign value into nested object using dot path
        const assignDeep = (obj, path, value) => {
            const keys = path.split('.');
            let current = obj;

            while (keys.length > 1) {
                const key = keys.shift();
                if (!current[key] || typeof current[key] !== 'object') {
                    current[key] = {};
                }
                current = current[key];
            }

            current[keys[0]] = value;
        };

        // Build nested object from full form
        for (const [key, value] of fd.entries()) {
            assignDeep(result, toDot(key), value);
        }

        // Apply the changed field override
        if (changedElement) {
            const path = toDot(changedElement.getAttribute('name'));
            assignDeep(result, path, changedValue);
        }

        return result;
    }
    
    const live = {
        update: function(e, el, config) {
            live.handleAjax(e, el, config);
        },
        handleAjax: function(e, el, config) {
            const form = el.closest('form');
            
            if (!form || form.tagName.toLowerCase() !== 'form') {
                return;
            }
            
            let value = el.value;
            
            if (el.getAttribute('type') === 'checkbox') {
                value = Array
                    .from(document.querySelectorAll('input[name="'+el.getAttribute('name')+'"]:checked'))
                    .map(checkbox => checkbox.value);
                
                if (! el.getAttribute('name').endsWith('[]')) {
                    value = value[0] ?? '';
                }
            }

            const changedPath = el.getAttribute('name')
                .replaceAll('[]', '')
                .replaceAll('[', '.')
                .replaceAll(']', '');

            const formData = serializeFormToNestedObject(form, el, value);

            formData._changed = changedPath;
            
            const inputMethod = form.querySelector('input[name="_method"]');
            const queryParams = new URLSearchParams(window.location.search);
            const queryData = Object.fromEntries(queryParams.entries());
            
            fetch(form.getAttribute('action'), {
                method: inputMethod ? inputMethod.value : form.getAttribute('method'),
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-Crud-Live": "1",
                    "Accept": "application/json"
                },
                body: JSON.stringify({...formData, ...queryData})
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 200) {
                    let replaces = config.fields.map((name) => '[data-field="'+name+'"]');
                    replaces = replaces.concat(config.selectors);

                    const doc = (new DOMParser()).parseFromString(data.html, 'text/html');

                    replaces.forEach(selector => {
                        const newEl = doc.querySelector(selector);
                        const oldEl = document.querySelector(selector);
                        
                        if (newEl && oldEl) {
                            oldEl.parentNode.replaceChild(newEl, oldEl);
                            return;
                        }
                        
                        if (oldEl && newEl === null) {
                            oldEl.remove();
                            return;
                        }
                        
                        if (newEl && oldEl === null) {
                            if (newEl.previousElementSibling) {
                                document.querySelector('[data-field="'+newEl.previousElementSibling.getAttribute('data-field')+'"]').after(newEl);
                            } else if (newEl.nextElementSibling) {
                                document.querySelector('[data-field="'+newEl.nextElementSibling.getAttribute('data-field')+'"]').before(newEl);
                            }
                        }
                    });
                    
                    const msgEl = el.parentNode.parentNode.querySelector('.form-message');
                    
                    if (msgEl) {
                        msgEl.remove();
                    }
                } else {
                    if (
                        typeof data['messages'] === 'undefined'
                        || typeof data.messages[0]  === 'undefined'
                    ) {
                        return;
                    }
                    
                    const message = data.messages[0];
                    let msgEl = el.parentNode.parentNode.querySelector('.form-message');
                    
                    if (msgEl === null) {
                        msgEl = document.createElement('div');
                        msgEl.classList.add('form-message', 'error', 'mt-xs');
                        
                        if (
                            (el.getAttribute('type') === 'checkbox' || el.getAttribute('type') === 'radio')
                            && el.parentNode.tagName.toLowerCase() === 'span'
                        ) {
                            el.parentNode.parentNode.appendChild(msgEl);
                        } else {
                            el.after(msgEl);
                        }
                    }
                    
                    msgEl.textContent = message.message;
                }
            });
        }
    };
    
    document.addEventListener('DOMContentLoaded', (e) => {
        // we add events globally as not to loose listeners
        // on update DOM
        let globalTimeout = null;

        ['keyup', 'change', 'focusout'].forEach(evt => {
            document.addEventListener(evt, e => {
                const el = e.target.closest('[data-live]');
                
                if (el === null) {
                    return;
                }
                
                const config = JSON.parse(el.getAttribute('data-live'));
                
                if (e.type === 'keyup' && config.debounce > 0) {
                    if (globalTimeout != null) {
                        clearTimeout(globalTimeout);
                    }

                    globalTimeout = setTimeout(() => {
                        globalTimeout = null;
                        live.update(e, el, config);
                    }, config.debounce);
                } else if (e.type === 'change' && !config.blur) {
                    event.preventDefault();
                    live.update(e, el, config);
                } else if (e.type === 'focusout' && config.blur) {
                    event.preventDefault();
                    live.update(e, el, config);
                }
            });
        });
    });
    
    return live;
    
})(window, document);

export default live;