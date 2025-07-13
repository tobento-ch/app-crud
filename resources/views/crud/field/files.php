<?php
$form = $view->form();
?>
<div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>">
    <div class="field-label">
        <?= $form->label(
            text: $field->label(),
            for: $field->name().'.src.',
            requiredText: $field->getRequiredText(action: $actionName),
            optionalText: $field->getOptionalText(action: $actionName),
        ) ?>
    </div>
    <div class="field-body" data-files-files="<?= $view->esc($field->name()) ?>">
        <?php if (!empty($files)) { ?>
            <?php foreach($files as $file) { ?>
                <div class="mb-s" data-files-file=""><?= $file->render() ?></div>
            <?php } ?>
        <?php }?>
        <?php foreach ($filesMessages as $message) { ?>
            <span class="form-message error"><?= $view->esc($message->message()) ?></span>
        <?php } ?>
        <?= $form->input(
            name: $field->name().'.src.',
            type: 'file',
            attributes: ['multiple', 'accept' => $field->acceptAttribute()],
        ) ?>
        <?php if ($field->getInfoText(action: $actionName)) { ?>
            <p class="text-xxs"><?= $view->esc($field->getInfoText(action: $actionName)) ?></p>
        <?php } ?>
    </div>
</div>