<?php $form = $view->form(); ?>

<div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>">
    <div class="field-label">
        <?= $form->label(
            text: $field->label(),
            for: $hasLabels ? null : $field->name(),
            requiredText: $field->getRequiredText(action: $actionName),
            optionalText: $field->getOptionalText(action: $actionName),
        ) ?>
    </div>
    <div class="field-body">
        <?= $body ?>
        <?php if ($field->getInfoText(action: $actionName)) { ?>
            <p class="text-xxs mt-xs"><?= $view->esc($field->getInfoText(action: $actionName)) ?></p>
        <?php } ?>
    </div>
</div>