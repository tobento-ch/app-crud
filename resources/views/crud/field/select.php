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
        <?= $view->esc($field->getInfoText(action: $actionName, below: false)) ?>
        <?= $body ?>
        <?= $view->esc($field->getInfoText(action: $actionName, below: true)) ?>
    </div>
</div>