const fieldFile = (function(window, document) {
    'use strict';

    const fieldFile = {
        register: function() {
            // we add click event globally as not to loose listeners on update DOM
            document.addEventListener('click', (e) => {
                const el = e.target.closest('[data-file-file]');

                if (el) {
                    fieldFile.handleClickAction(e, el);
                }
            });
        },
        handleClickAction: function(e, fileEl) {
            if (! e.target.hasAttribute('data-action')) {
                return;
            }
            
            const action = e.target.getAttribute('data-action');
            const fieldEl = e.target.closest('[data-field]');
            let itemsEl = null;
            let itemEl = null;
            
            switch (action) {
                case 'file-edit':
                    const fileEditEl = fieldEl.querySelector('[data-file-edit]');
                    fileEditEl.classList.toggle('display-none');
                    e.target.classList.toggle('active');
                    break;
                case 'file-delete':
                    fileEl.querySelectorAll('[data-file]').forEach(el => {
                        const inp = document.createElement('input');
                        inp.setAttribute('name', el.getAttribute('name'));
                        inp.setAttribute('type', 'hidden');
                        inp.setAttribute('value', '');
                        fieldEl.prepend(inp);
                    });
                    
                    fileEl.parentNode.removeChild(fileEl);
                    break;
                case 'move.up':
                    itemsEl = e.target.closest('[data-files-files]');
                    itemEl = e.target.closest('[data-files-file]');
                    if(itemEl.previousElementSibling) {
                        itemEl.parentNode.insertBefore(itemEl, itemEl.previousElementSibling);
                    }
                    fieldFile.reorderFiles(itemsEl);
                    break;
                case 'move.down':
                    itemsEl = e.target.closest('[data-files-files]');
                    itemEl = e.target.closest('[data-files-file]');
                    if(itemEl.nextElementSibling && itemEl.nextElementSibling.hasAttribute('data-files-file')) {
                        itemEl.parentNode.insertBefore(itemEl.nextElementSibling, itemEl);
                    }
                    fieldFile.reorderFiles(itemsEl);
                    break;
            }
        },
        reorderFiles: function(itemsEl) {
            const items = itemsEl.querySelectorAll('[data-order]');
            let index = 0;
            items.forEach(el => {
                el.setAttribute('value', index);
                index++;
            });
        }
    };
    
    document.addEventListener('DOMContentLoaded', (e) => {
        fieldFile.register();
    });
    
    return fieldFile;
    
})(window, document);

export default fieldFile;