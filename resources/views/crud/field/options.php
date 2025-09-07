<?php
$view->asset('assets/crud/field-options.js')->attr('type', 'module');

$form = $view->form();
$name = $form->nameToArray($field->name().'.');
?>
<div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>">
    <div class="field-label">
        <?= $form->label(
            text: $field->label(),
            for: 'search.'.$field->name(),
            requiredText: $field->getRequiredText(action: $actionName),
            optionalText: $field->getOptionalText(action: $actionName),
        ) ?>
    </div>
    <div class="field-body" data-options="<?= $view->esc($field->name()) ?>">
        <div class="crud-options" data-selected="<?= $view->esc($field->name()) ?>">
            <?php foreach($selectedOptions as $item) { ?>
                <?php $option = $field->createOption($item, $view, $field); ?>
                <div class="crud-option" data-options-action="remove">
                    <label>
                        <input name="<?= $view->esc($name) ?>" type="checkbox" value="<?= $view->esc($option->value()) ?>" checked>
                        <?= $option->getHtml() ?>
                    </label>
                </div>
            <?php } ?>
        </div>
        <div class="my-s">
            <?= $form->input(
                name: 'search.'.$field->name(),
                type: 'search',
                value: '',
                attributes: [
                    'data-options-action' => 'search',
                    'aria-label' => $view->trans('Search'),
                    'placeholder' => $field->getPlaceholder()
                ],
            ) ?>
        </div>
        <?php if ($field->getInfoText(action: $actionName)) { ?>
            <p class="text-xxs mt-xs mb-s"><?= $view->esc($field->getInfoText(action: $actionName)) ?></p>
        <?php } ?>
        <div class="crud-options" data-unselected="<?= $view->esc($field->name()) ?>">
            <?= $form->getMessage($form->nameToArray($field->name())) ?>
            <?php foreach($unselectedOptions as $item) { ?>
                <?php $option = $field->createOption($item, $view, $field); ?>
                <div class="crud-option" data-options-action="add">
                    <label>
                        <input name="<?= $view->esc($name) ?>" type="checkbox" value="<?= $view->esc($option->value()) ?>">
                        <?= $option->getHtml() ?>
                    </label>
                </div>
            <?php } ?>
        </div>
        <?= $form->input(
            name: $name,
            type: 'hidden',
            value: $field->getEmptyOption(),
            attributes: ['id' => null],
        ) ?>
    </div>
</div>