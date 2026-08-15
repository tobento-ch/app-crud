import modals from './../modal/modals.js';
import notifier from './../js-notifier/notifier.js';
import validation from './../form/validation.js';

const button = (function(window, document) {
    'use strict';

    const button = {
        listeners: {},
        handleClickEvent: function(e, el) {
            if (el.hasAttribute('data-button-ajax') && !el.hasAttribute('data-confirm')) {
                button.handleAjax(e, el);
                return;
            }
            
            if (el.hasAttribute('data-confirm')) {
                return; // this click only opens the confirm modal; withModal() owns the rest
            }
            
            if (el.hasAttribute('data-loading')) {
                button.handleLoading(e, el);
            }
        },
        handleLoading: function(e, el) {
            const form = el.closest('form');

            if (form.tagName.toLowerCase() !== 'form') {
                return
            }
            
            if (form.checkValidity() === false) {
                return;
            }
            
            el.classList.add('loading');
            setTimeout(() => {
                el.setAttribute('disabled', 'disabled');
            }, 100);
        },
        handleAjax: function(e, el) {
            const form = el.closest('form');

            if (form.tagName.toLowerCase() !== 'form') {
                return
            }

            const formData = new FormData(form);
            
            if (form.checkValidity() === false) {          
                return;
            }
            
            e.preventDefault();
            el.classList.add('loading');
            el.setAttribute('disabled', 'disabled');
            
            if (el.getAttribute('name') === 'next_action') {
                formData.append('next_action', el.getAttribute('value'));
            }

            fetch(form.getAttribute('action'), {
                method: form.getAttribute('method'),
                body: formData,
            }).then(response => {
                // 1. Handle 4xx / 5xx responses FIRST
                if (!response.ok) {
                    return response.text();
                }

                // 2. Handle real redirects
                let [uri, hash] = window.location.href.split("#");
                if (response.url !== uri) {
                    window.location.href = response.url;
                    return null;
                }

                // 3. Normal AJAX response
                return response.text();
            }).then(string => {
                const replaces = ['[data-table-group="items"]', '[data-ajax="refresh"]'];
                const doc = (new DOMParser()).parseFromString(string, 'text/html');
                
                replaces.forEach(selector => {
                    const newEl = doc.querySelector(selector);
                    const oldEl = document.querySelector(selector);
                    if (newEl && oldEl) {
                        oldEl.parentNode.replaceChild(newEl, oldEl);
                    }
                });

                el.classList.remove('loading');
                el.removeAttribute('disabled');
                button.fire('dom.updated', [el]);
                validation.register();
                
                // close modal if there is one:
                if (el.hasAttribute('data-modal-trigger')) {
                    const modalId = el.getAttribute('data-modal-trigger');
                    if (modals.has(modalId)) {
                        modals.get(modalId).close();
                    }
                }
                
                // scroll to error if there is one:
                const formErrorEl = document.querySelector('.form-message.error');
                
                if (formErrorEl) {
                    button.unhideElement(formErrorEl);
                    formErrorEl.scrollIntoView({behavior: 'smooth'});
                }
                
                const messages = doc.querySelectorAll('[data-message]');
                
                messages.forEach(el => {
                    notifier.send({
                        status: el.getAttribute('data-message'),
                        text: el.textContent
                    });
                });
                
                if (messages.length === 0 && el.getAttribute('data-button-ajax') !== '') {
                    notifier.send({
                        status: 'success',
                        text: el.getAttribute('data-button-ajax')
                    });
                }
            });
        },
        unhideElement: function(el) {
            const hiddenEl = el.closest('.display-none');
            if (hiddenEl) {
                hiddenEl.classList.remove('display-none');
                button.unhideElement(hiddenEl);
            }
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
        // we add click event globally as not to loose listeners
        // on update DOM e.g. index action.
        document.addEventListener('click', (e) => {
            const el = e.target.closest('[data-button]');

            if (el) {
                button.handleClickEvent(e, el);
            }
        });
    });
    
    return button;
    
})(window, document);

export default button;