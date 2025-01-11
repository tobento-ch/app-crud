<?php $form = $view->form(); ?>

<div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>">
    <div class="field-label">
        <?= $form->label(
            text: $view->trans($field->label()),
            for: $hasLabels ? null : $field->name(),
            requiredText: $view->trans($field->getRequiredText(action: $actionName)),
            optionalText: $view->trans($field->getOptionalText(action: $actionName)),
        ) ?>
    </div>
    <div class="field-body">
        <?= $body ?>
        <?php if ($field->getInfoText(action: $actionName)) { ?>
            <p class="text-xxs"><?= $view->etrans($field->getInfoText(action: $actionName)) ?></p>
        <?php } ?>
    </div>
</div>