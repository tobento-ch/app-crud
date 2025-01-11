import modals from './../modal/modals.js';

const crudConfirm = (function(window, document) {
    'use strict';

    const confirm = {
        handleClick: function(e, el) {
            if (el.getAttribute('data-confirm') === '') {
                confirm.inlined(e, el);
            } else {
                confirm.withModal(e, el);
            }
        },
        inlined: function(e, el) {
            if (!el.hasAttribute('data-confirming')) {
                e.preventDefault();
                e.stopPropagation();
                el.setAttribute('data-confirming', el.textContent);
                el.innerHTML = '<span data-type="confirm" class="link">✔</span><span data-type="cancel" class="link pl-s">✘</span>';
            } else {
                if (e.target.getAttribute('data-type') === 'confirm') {
                    el.removeAttribute('data-confirming');
                    el.removeAttribute('data-confirm');
                } else {
                    e.preventDefault();
                    e.stopPropagation();
                    el.setAttribute('data-confirm', '');
                    el.textContent = el.getAttribute('data-confirming');
                    el.removeAttribute('data-confirming');
                }
            }
        },
        withModal: function(e, el) {
            if (!modals.has('confirm')) {
                el.removeAttribute('data-confirm');
                return;
            }
            
            const modal = modals.get('confirm');
            
            if (e.target.hasAttribute('data-confirming')) {
                el.removeAttribute('data-confirm');
                return;
            }
            
            e.preventDefault();
            e.stopPropagation();

            let form = el.parentNode.cloneNode(true);
            const btn = form.querySelector('[data-confirm]');
            
            if (form.tagName.toLowerCase() !== 'form') {
                form = btn;
            }
            
            form.setAttribute('data-btn', 'confirm');
            btn.setAttribute('data-confirming', '');
            btn.setAttribute('data-modal-trigger', 'confirm');
            btn.removeAttribute('data-confirm');
            btn.classList.remove('raw');
            btn.classList.add('button', 'primary');
            modal.modalEl.querySelector('[data-btn="confirm"]').replaceWith(form);
            modal.modalEl.querySelector('.modal-body p').textContent = el.getAttribute('data-confirm');
            modal.open();
        }
    };
    
    document.addEventListener('DOMContentLoaded', (e) => {
        // we add click event globally as not to loose listeners
        // on update DOM e.g. index action.
        document.addEventListener('click', (e) => {
            const el = e.target.closest('[data-confirm]');

            if (el) {
                confirm.handleClick(e, el);
            }
        });
    });
    
    return confirm;
    
})(window, document);

export default crudConfirm;