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
                    e.preventDefault();
                    e.stopPropagation();

                    el.removeAttribute('data-confirming');
                    el.removeAttribute('data-confirm');

                    // Restore original label
                    el.textContent = el.getAttribute('data-confirming');

                    // Trigger the click again so if button ajax action is called if set.
                    setTimeout(() => el.click(), 0);
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

            const originalForm = el.closest('form');

            // Clone full form so all fields are preserved
            let form = originalForm.cloneNode(true);

            // Hide the cloned form visually but keep it in DOM
            form.style.display = 'none';

            // Create a small visible confirm form with just the button
            let visibleForm = document.createElement('form');
            visibleForm.method = originalForm.method;
            visibleForm.action = originalForm.action;
            visibleForm.enctype = originalForm.enctype;

            // On submit of visible form, submit the hidden full form instead
            visibleForm.addEventListener('submit', function(ev) {
                ev.preventDefault();
                form.submit();
            });

            const btn = el.cloneNode(true);
            btn.removeAttribute('data-confirm');
            btn.setAttribute('data-confirming', '');
            btn.setAttribute('data-modal-trigger', 'confirm');
            btn.classList.remove('raw');
            btn.classList.add('button', 'primary');

            visibleForm.appendChild(btn);
            visibleForm.setAttribute('data-btn', 'confirm');

            // Insert both into modal: hidden full form and visible confirm form
            const container = modal.modalEl.querySelector('[data-btn="confirm"]');
            container.replaceWith(visibleForm);
            modal.modalEl.appendChild(form);

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