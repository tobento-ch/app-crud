<?php
$form = $view->form();
?>
<div class="modal modal-fade" data-modal='{"id": "bulk-delete"}'>
    <div class="modal-background"></div>
    <div class="modal-content modal-l">
        <?= $form->form(['action' => $action->getUrl()]) ?>
        <div class="modal-body">
            <p class="py-xs text-body"><?= $view->etrans('Are you sure you want to delete all selected items?') ?></p>
        </div>
        <div class="modal-foot">
            <div class="buttons spaced">
                <span class="button primary" data-bulk-save="bulk-delete"><?= $view->etrans('Delete') ?></span>
                <span class="link modal-close"><?= $view->etrans('Cancel') ?></span>
            </div>
        </div>
        <?= $form->close() ?>
    </div>
</div>