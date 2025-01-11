<?php
$view->asset('assets/crud/field-file-source.js')->attr('type', 'module');
$view->asset('assets/modal/modals.css');
$view->asset('assets/media/image-editors.css');
$view->asset('assets/js-cropper/cropper.css');
$view->asset('assets/media/image-crop.js')->attr('type', 'module');

$form = $view->form();
?>
<div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>">
    <?php if ($field->label()) { ?>
        <div class="field-label">
            <?= $form->label(
                text: $view->trans($field->label()),
                for: $field->name(),
                requiredText: $view->trans($field->getRequiredText(action: $actionName)),
                optionalText: $view->trans($field->getOptionalText(action: $actionName)),
            ) ?>
        </div>
    <?php } ?>
    <div class="field-body">
        <?php if ($file) { ?>
            <div class="cols" data-file-source="<?= $view->esc($field->name()) ?>">
                <?php if ($picture) { ?>
                    <div class="col-2 pr-m"><?= $picture ?></div>
                <?php } ?>
                <div class="col-10">
                    <div class="mb-xxs"><?= $view->esc($file->path()) ?></div>
                    <div class="mb-xxs"><?= $view->esc($file->humanSize()) ?></div>
                    <div class="file-actions">
                        <span class="link" data-action="delete"><?= $view->etrans('delete') ?></span>
                        <?php if ($actionName !== 'copy' && $imageEditorUrl) { ?>
                            <span class="link"><a data-action="edit" href="<?= $view->esc($imageEditorUrl) ?>"><?= $view->etrans('edit') ?></a></span>
                        <?php } ?>
                        <?php if ($actionName !== 'copy' && $pictureEditorUrl) { ?>
                            <span class="link"><a data-action="edit-picture" href="<?= $view->esc($pictureEditorUrl) ?>"><?= $view->etrans('edit picture') ?></a></span>
                        <?php } ?>
                        <span class="link" data-action="change"><?= $view->etrans('change') ?></span>
                    </div>
                    <div class="display-none mt-xs" data-change="">
                        <?= $form->input(
                            name: $field->name(),
                            type: 'file',
                            attributes: ['data-file' => '', 'accept' => $field->acceptAttribute(), 'disabled'],
                            withInput: false,
                        ) ?>
                    </div>
                    <?php if ($actionName === 'copy') { ?>
                        <?= $form->input(
                            name: $field->name().'.path',
                            type: 'hidden',
                            value: $entity->get($field->name(), ''),
                        ) ?>
                    <?php } ?>
                </div>
            </div>
        <?php } else { ?>
            <?= $form->input(
                name: $field->name(),
                type: 'file',
                attributes: ['accept' => $field->acceptAttribute()],
                withInput: false,
            ) ?>
            <?php if ($field->getInfoText(action: $actionName)) { ?>
                <p class="text-xxs"><?= $view->etrans($field->getInfoText(action: $actionName)) ?></p>
            <?php } ?>
        <?php } ?>
    </div>
    
    <div class="modal" data-modal='{"id": "crud-image-editor"}'>
        <div class="modal-background"></div>
        <div class="modal-content modal-l">
            <div class="modal-body"><!-- --></div>
            <div class="modal-foot">
                <div class="buttons spaced">
                    <span class="button primary" data-modal-save=""><?= $view->etrans('Save') ?></span>
                    <span class="link modal-close"><?= $view->etrans('close') ?></span>
                </div>
            </div>
        </div>
    </div>
</div>