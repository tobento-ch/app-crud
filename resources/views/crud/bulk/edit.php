<?php
$form = $view->form();
$field = $action->field();
?>
<div class="modal modal-fade" data-modal='{"id": "<?= $view->esc($field->name()) ?>"}'>
    <div class="modal-background"></div>
    <div class="modal-content modal-l">
        <div class="modal-body" data-bulk-ajax-refresh="<?= $view->esc($field->name()) ?>">
            <?= $form->form([
                'action' => $action->getUrl(),
                'name' => $field->name(),
            ]) ?>
            <?php foreach($action->fields()->column('groupName', 'groupId') as $groupId => $groupName) { ?>
                <section class="fields" data-fields-group="<?= $view->esc($groupId) ?>">
                    <h2 class="group-title"><?= $view->esc($groupName) ?></h2>
                    <?php
                    foreach($action->fields()->group($groupName) as $f) {
                        echo $f->render();
                    }
                    ?>
                </section>
            <?php } ?>
            <?= $form->close() ?>
        </div>
        <div class="modal-foot">
            <div class="buttons spaced">
                <button class="button primary" data-bulk-save="<?= $view->esc($field->name()) ?>"><?= $view->etrans('Save') ?></button>
                <span class="link modal-close"><?= $view->etrans('Cancel') ?></span>
            </div>
        </div>
    </div>
</div>