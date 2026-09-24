'use strict';

import modals from './../modal/modals.js';

function initModalButtonSearch(modalId) {
    
    const modal = modals.get(modalId);
    const input = modal.modalEl.querySelector('input[name="buttons_search"]');

    if (!input) { return; }
    
    modal.listen('open', (modal) => {
        setTimeout(() => {
            const input = modal.modalEl.querySelector('input[name="buttons_search"]');

            if (input) {
                input.value = '';
                input.focus();
            }
        }, 50);
    });
    
    input.addEventListener('keyup', (e) => {
        const searchTerm = input.value.toLowerCase();

        modal.modalEl.querySelectorAll('[data-button]').forEach(el => {
            // Try to find inner searchable element
            let contentEl = el.querySelector('[data-button-search]');

            // Fallback: the element itself is searchable
            if (!contentEl && el.hasAttribute('data-button-search')) {
                contentEl = el;
            }

            // If nothing is searchable, skip
            if (!contentEl) return;
            
            const txtValue = contentEl.textContent || contentEl.innerText;

            if (txtValue.toLowerCase().indexOf(searchTerm) !== -1) {
                el.classList.remove('display-none');
            } else {
                el.classList.add('display-none');
            }
        });
        
        // Hide groups with no visible buttons
        modal.modalEl.querySelectorAll('.modal-group').forEach(group => {
            const visibleButtons = group.querySelectorAll('[data-button]:not(.display-none)');
            if (visibleButtons.length === 0) {
                group.classList.add('display-none');
            } else {
                group.classList.remove('display-none');
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', (e) => {
    document.querySelectorAll('.crud-button-modal').forEach(el => {
        const modalId = el.getAttribute('data-modal-id');
        if (modalId) {
            initModalButtonSearch(modalId);
        }
    });
});