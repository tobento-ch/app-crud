const fieldItems = (function(window, document) {
    'use strict';

    const fieldItems = {
        register: function() {
            // we add click event globally as not to loose listeners on update DOM
            document.addEventListener('click', (e) => {
                const el = e.target.closest('[data-items-items]');

                if (el) {
                    fieldItems.handleClickAction(e);
                }
            });
        },
        handleClickAction: function(e) {
            if (! e.target.hasAttribute('data-items-action')) {
                return;
            }
            
            const action = e.target.getAttribute('data-items-action');
            const itemsEl = e.target.closest('[data-items-items]');
            const items = itemsEl.querySelectorAll('[data-items-item]');
            const itemsLength = items.length;
            let itemEl = null;
            
            switch (action) {
                case 'move.up':
                    itemEl = e.target.closest('[data-items-item]');
                    if(itemEl.previousElementSibling) {
                        itemEl.parentNode.insertBefore(itemEl, itemEl.previousElementSibling);
                    }
                    fieldItems.reindexItems(itemsEl);
                    break;
                case 'move.down':
                    itemEl = e.target.closest('[data-items-item]');
                    if(itemEl.nextElementSibling && itemEl.nextElementSibling.hasAttribute('data-items-item')) {
                        itemEl.parentNode.insertBefore(itemEl.nextElementSibling, itemEl);
                    }
                    fieldItems.reindexItems(itemsEl);
                    break;
                case 'new':
                    const templateEl = itemsEl.querySelector('[data-items-template]');
                    itemEl = templateEl.content.cloneNode(true);
                    
                    if (itemsLength === 0) {
                        itemsEl.prepend(itemEl);
                    } else {
                        items[0].parentNode.insertBefore(itemEl, items[itemsLength-1].nextSibling);
                    }
                    
                    fieldItems.reindexItems(itemsEl);
                    
                    const inpEl = itemsEl.querySelector('[data-items-empty]');
                    if (inpEl) {
                        inpEl.remove();
                    }
                    break;
                case 'delete':
                    itemEl = e.target.closest('[data-items-item]');
                    itemEl.parentNode.removeChild(itemEl);
                    fieldItems.reindexItems(itemsEl);
                    const len = itemsEl.querySelectorAll('[data-items-item]').length;
                    if (len === 0) {
                        const inp = document.createElement('input');
                        inp.setAttribute('name', itemsEl.getAttribute('data-items-items'));
                        inp.setAttribute('type', 'hidden');
                        inp.setAttribute('data-items-empty', '');
                        itemsEl.prepend(inp);
                    }
                    break;
            }
        },
        reindexItems: function(itemsEl) {
            const items = itemsEl.querySelectorAll('[data-items-item]');
            let index = 1;
            items.forEach(el => {
                fieldItems.renameAttributes(el.querySelectorAll('[name]'), index);
                fieldItems.renameAttributes(el.querySelectorAll('[for]'), index);
                fieldItems.renameAttributes(el.querySelectorAll('[data-field]'), index);
                index++;
            });
        },
        renameAttributes: function(els, index) {
            els.forEach(el => {
                fieldItems.renameAttribute(el, 'id', index);
                fieldItems.renameAttribute(el, 'name', index);
                fieldItems.renameAttribute(el, 'for', index);
                fieldItems.renameAttribute(el, 'data-field', index);
            });
        },
        renameAttribute: function(el, name, index) {
            if (! el.hasAttribute(name)) {
                return;
            }
            
            let value = el.getAttribute(name);
            
            if (value.includes('{num}')) {
                value = value.replace('{num}', index);
            } else {
                value = value.replace(/[0-9]+/, index);
            }
            
            el.setAttribute(name, value);
        }
    };
    
    document.addEventListener('DOMContentLoaded', (e) => {
        fieldItems.register();
    });
    
    return fieldItems;
    
})(window, document);

export default fieldItems;