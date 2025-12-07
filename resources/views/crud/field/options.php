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
                <?php
                $option = $field->createOption($item, $view, $field);

                $inputAttributes = new \Tobento\Service\Tag\Attributes($field->assignLiveAttributes(
                    action: $actionName,
                    attributes: [
                        'name' => $name,
                        'type' => 'checkbox',
                        'value' => $option->value(),
                        'checked',
                    ],
                ));
                ?>
                <div class="crud-option" data-options-action="remove">
                    <label>
                        <input<?= (string)$inputAttributes ?>>
                        <?= $option->getHtml() ?>
                    </label>
                </div>
            <?php } ?>
        </div>
        <?= $view->esc($field->getInfoText(action: $actionName, below: false)) ?>
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
        <?= $view->esc($field->getInfoText(action: $actionName, below: true)) ?>
        <div class="crud-options" data-unselected="<?= $view->esc($field->name()) ?>">
            <?= $form->getMessage($form->nameToArray($field->name())) ?>
            <?php foreach($unselectedOptions as $item) { ?>
                <?php
                $option = $field->createOption($item, $view, $field);
                
                $inputAttributes = new \Tobento\Service\Tag\Attributes($field->assignLiveAttributes(
                    action: $actionName,
                    attributes: [
                        'name' => $name,
                        'type' => 'checkbox',
                        'value' => $option->value(),
                    ],
                ));
                ?>
                <div class="crud-option" data-options-action="add">
                    <label>
                        <input<?= (string)$inputAttributes ?>>
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