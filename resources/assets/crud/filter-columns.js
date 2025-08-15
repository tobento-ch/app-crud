import sortables from './../js-sortable/sortables.js';
import crud from './index-action.js';

const filterColumns = (function(window, document) {
    'use strict';

    function createSortables() {
        document.querySelectorAll('[data-filter-sortable]').forEach(el => {
            const id = el.getAttribute('data-filter-sortable');
            
            sortables.delete(id);
            
            const sortable = sortables.create(el, {
                id: id,
                selector: '.drag-item',
                handle: '.crud-drag',
                ghost: false,
            });
            
            sortable.listen('drop', (event, sortable) => {
                const form = sortable.el.closest('form');

                if (form && form.hasAttribute('data-form-filter')) {
                    const queryString = new URLSearchParams(new FormData(form)).toString();
                    const uri = form.getAttribute('action')+'?'+queryString;

                    crud.updateFilter(uri, event);
                }
            });
        });
    }
    
    document.addEventListener('DOMContentLoaded', (e) => {
        
        crud.listen('filter.updated', () => {
            createSortables();
        });
        
        createSortables();
    });
    
    const filterColumns = null;
    return filterColumns;
})(window, document);

export default filterColumns;