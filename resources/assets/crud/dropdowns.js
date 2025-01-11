'use strict';

function handleDropdownClick(e, el) {
    if (! event.target.closest('.crud-dropdown-menu')) {
        el.classList.toggle('open');
    }
}

function closeDropdowns(exceptEl = null) {
    document.querySelectorAll('[data-dropdown]').forEach(el => {
        if (el !== exceptEl) {
            el.classList.remove('open');
        }
    });
}

document.addEventListener('DOMContentLoaded', (e) => {
    // we add click event globally as not to loose listeners
    // on update DOM e.g. index action.
    document.addEventListener('click', (e) => {
        const el = e.target.closest('[data-dropdown]');

        if (el) {
            closeDropdowns(el);
            handleDropdownClick(e, el);
        } else {
            closeDropdowns();
        }
    });
});