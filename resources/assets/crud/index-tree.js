import sortables from './../js-sortable/sortables.js';
import crud from './index-action.js';
import notifier from './../js-notifier/notifier.js';

const indexTree = (function(window, document) {
    'use strict';

    function createSortables() {
        const items = document.querySelector('[data-table-group="items"]');

        sortables.delete('items');

        const sortable = sortables.create(items, {
            id: 'items',
            selector: 'li',
            nestable: true,
            handle: '.crud-drag',
            ghost: false,
        });

        sortable.listen('drop', (event, sortable) => {
            // Get the ul element where the item was dropped:
            const ul = sortable.draggable.closest('ul');
            
            // Determine the parent id:
            let parentId = "0";
            const parent = ul.parentNode;
            
            if (parent && parent.hasAttribute('data-id')) {
                parentId = parent.getAttribute('data-id');
            }
            
            // Get only those li elements within the current ul element (without children):
            const items = ul.querySelectorAll(':scope > li');
            
            // Build your data structure to update your items using the Fetch API.
            const data = {};
            const length = items.length;
            
            for (let i = 0; i < length; i++) {
                data[i] = {};
                data[i]['id'] = items[i].getAttribute('data-id');
                data[i]['parent_id'] = parentId;
                data[i]['sortorder'] = i+1;
            }
            
            const urlEl = document.querySelector('[data-tree-update-url]');
            
            if (urlEl) {
                updateTree(urlEl.getAttribute('data-tree-update-url'), {items: data});
            }
        });
    }
    
    function updateTree(url, data) {
        fetch(url, {
            method: 'POST',
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 200) {
                const items = data.items;
                const length = items.length;
                
                for (let i = 0; i < length; i++) {
                    let row = document.querySelector('[data-id="'+items[i]['id']+'"]');
                    
                    if (row) {
                        let el = row.querySelector('[data-field="'+data.sortorderName+'"]');
                        if (el) {
                            el.textContent = items[i]['sortorder'];
                        }
                        let parent = row.querySelector('[data-field="'+data.parentIdName+'"]');
                        if (parent) {
                            parent.textContent = items[i]['parent_id'];
                        }
                    }
                }
                
                const msg = document.querySelector('[data-tree-success-message]');
                
                if (msg) {
                    notifier.send({
                        status: 'success',
                        text: msg.getAttribute('data-tree-success-message')
                    });
                }
            } else {
                const msg = document.querySelector('[data-tree-error-message]');
                
                if (msg) {
                    notifier.send({
                        status: 'error',
                        text: msg.getAttribute('data-tree-error-message')
                    });
                }
            }
        });
    }
    
    document.addEventListener('DOMContentLoaded', (e) => {
        
        crud.listen('filter.updated', () => {
            createSortables();
        });
        
        createSortables();
    });
    
    const indexTree = null;
    return indexTree;
})(window, document);

export default indexTree;