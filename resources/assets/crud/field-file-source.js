import imageEditors from './../media/image-editors.js';
import pictureEditors from './../media/picture-editors.js';
import modals from './../modal/modals.js';

const fieldFileSource = (function(window, document) {
    'use strict';

    const fieldFileSource = {
        register: function() {
            // we add click event globally as not to loose listeners on update DOM
            document.addEventListener('click', (e) => {
                const el = e.target.closest('[data-file-source]');

                if (el) {
                    fieldFileSource.handleClickAction(e);
                }
            });
        },
        handleClickAction: function(e) {
            if (! e.target.hasAttribute('data-action')) {
                return;
            }
            
            const action = e.target.getAttribute('data-action');
            const fieldEl = e.target.closest('[data-field]');
            const fileEl = e.target.closest('[data-file-source]');

            switch (action) {
                case 'change':
                    const changeEl = fileEl.querySelector('[data-change]');
                    changeEl.classList.toggle('display-none');
                    changeEl.querySelector('input').toggleAttribute('disabled');
                    e.target.classList.toggle('active');
                    break;
                case 'edit':
                    e.preventDefault();
                    var url = e.target.getAttribute('href');
                    var modal = modals.get('crud-image-editor');
                    
                    document.body.appendChild(modal.modalEl); // to fix css relative
                    
                    modals.get('crud-image-editor').listen('close', (modal) => {
                        modal.modalEl.querySelector('.modal-body').innerHTML = '';
                        modal.listeners = {};
                    });
                    
                    modals.get('crud-image-editor').listen('open', (modal) => {
                        fetch(url).then(response => {
                            return response.text();
                        }).then(string => {
                            const doc = (new DOMParser()).parseFromString(string, 'text/html');
                            const editorEl = doc.querySelector('.image-editor');
                            
                            if (editorEl === null) {
                                modal.modalEl.querySelector('.modal-body').innerHTML = doc.body.innerHTML;
                                return;
                            }
                            
                            modal.modalEl.querySelector('.modal-body').innerHTML = editorEl.innerHTML;
                            
                            imageEditors.delete('crud-img');
                            
                            const form = modal.modalEl.querySelector('form');
                            const config = JSON.parse(form.getAttribute('data-image-editor'));
                            config.id = 'crud-img';
                            const editor = imageEditors.create(form, config);
                            
                            editor.deleteAction('save');
                            editor.registerAction(modal.modalEl.querySelector('[data-modal-save]'), 'save', 'save');
                            
                            editor.listen('action.save', (action) => {
                                action.canSave = false;
                                
                                const form = editor.formEl;
                                const formData = new FormData(form);
                                
                                fetch(form.getAttribute('action'), {
                                    method: form.getAttribute('method'),
                                    body: formData,
                                    headers: {
                                        "Accept": "application/json"
                                    }
                                }).then(response => {
                                    if(response.ok) {
                                        return response.json();
                                    }
                                    return null;
                                }).then(data => {
                                    if (data !== null) {
                                        const pic = fileEl.querySelector('picture');
                                        if (pic) {
                                            const img = document.createElement('img');
                                            img.setAttribute('src', data.file.dataUrl);
                                            pic.replaceWith(img);                                            
                                        }
                                    }

                                    modal.close();
                                });
                            });
                        });
                    });

                    modal.open();
                    break;
                case 'edit-picture':
                    e.preventDefault();
                    var url = e.target.getAttribute('href');
                    var modal = modals.get('crud-image-editor');

                    document.body.appendChild(modal.modalEl); // to fix css relative
                    
                    modals.get('crud-image-editor').listen('close', (modal) => {
                        modal.modalEl.querySelector('.modal-body').innerHTML = '';
                        modal.listeners = {};
                    });
                    
                    modals.get('crud-image-editor').listen('open', (modal) => {
                        fetch(url).then(response => {
                            return response.text();
                        }).then(string => {
                            const doc = (new DOMParser()).parseFromString(string, 'text/html');
                            const editorEl = doc.querySelector('.picture-editor');
                            
                            if (editorEl === null) {
                                modal.modalEl.querySelector('.modal-body').innerHTML = doc.body.innerHTML;
                                return;
                            }
                            
                            modal.modalEl.querySelector('.modal-body').innerHTML = editorEl.innerHTML;
                            
                            pictureEditors.delete('1');
                            
                            const form = modal.modalEl.querySelector('form');
                            
                            if (!form) {
                                modal.modalEl.querySelector('[data-modal-save]').remove();
                                return;
                            }
                            
                            const editor = pictureEditors.create(form, {
                                id: '1',
                                createImageEditors: true,
                            });
                            
                            editor.deleteAction('save');
                            editor.registerAction(modal.modalEl.querySelector('[data-modal-save]'), 'save');
                            
                            editor.listen('action.save', (action) => {
                                action.canSave = false;
                                
                                const form = editor.formEl;
                                const formData = new FormData(form);
                                
                                fetch(form.getAttribute('action'), {
                                    method: form.getAttribute('method'),
                                    body: formData,
                                    headers: {
                                        "Accept": "application/json"
                                    }
                                }).then(response => {
                                    modal.close();
                                });
                            });
                        });
                    });

                    // open modal:
                    modal.open();
                    break;
                case 'delete':
                    fileEl.parentNode.removeChild(fileEl);
                    const inp = document.createElement('input');
                    inp.setAttribute('name', fileEl.querySelector('[data-file]').getAttribute('name'));
                    inp.setAttribute('type', 'hidden');
                    inp.setAttribute('value', '');
                    fieldEl.prepend(inp);
                    break;
            }
        }
    };
    
    document.addEventListener('DOMContentLoaded', (e) => {
        fieldFileSource.register();
    });
    
    return fieldFileSource;
    
})(window, document);

export default fieldFileSource;