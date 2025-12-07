<?php
$view->asset('assets/crud/field-single-options.js')->attr('type', 'module');

$form = $view->form();
?>
<div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>" data-field-type="single-options">
    <div class="field-label">
        <?= $form->label(
            text: $field->label(),
            for: 'search.'.$field->name(),
            requiredText: $field->getRequiredText(action: $actionName),
            optionalText: $field->getOptionalText(action: $actionName),
        ) ?>
    </div>
    <div class="field-body" data-options="<?= $view->esc($field->name()) ?>" data-display-modal="<?= $displayAsModal ? '1' : '' ?>">
        <?= $view->esc($field->getInfoText(action: $actionName, below: false)) ?>
        <?= $form->getMessage($form->nameToArray($field->name())) ?>
        <div class="crud-select-input-ctn">
            <button
                type="button"
                class="crud-select-input-btn"
                data-single-options-action="open-dropdown"
                data-selected="<?= $view->esc($field->name()) ?>"
            >
                <?php if ($selectedOption) { ?>
                    <div class="crud-select-option"><?= $selectedOption->getHtml() ?></div>
                <?php } else { ?>
                    <!-- -->
                <?php } ?>
            </button>
            <?php if (!$displayAsModal) { ?>
                <div class="crud-select-input-dropdown" data-dropdown="">
                    <div class="crud-select-search">
                        <?= $form->input(
                            name: 'search.'.$field->name(),
                            type: 'search',
                            value: '',
                            attributes: [
                                'class' => 'small fit',
                                'data-single-options-action' => 'search',
                                'aria-label' => $view->trans('Search'),
                                'placeholder' => $field->getPlaceholder()
                            ],
                        ) ?>
                    </div>
                    <div class="crud-select-options crud-select-input-dropdown-body" data-unselected="<?= $view->esc($field->name()) ?>">
                        <?php foreach($unselectedOptions as $item) { ?>
                            <?php $option = $field->createOption($item, $view, $field); ?>
                            <div class="crud-select-option" data-single-options-action="add" data-value="<?= $view->esc($option->value()) ?>">
                                <?= $option->getHtml() ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
        <?= $form->input(
            name: $field->name(),
            type: 'hidden',
            value: (string)$selectedOption?->value(),
            attributes: $field->assignLiveAttributes(
                action: $actionName,
                attributes: ['id' => null, 'data-field-input' => ''],
            ),
        ) ?>
        <?= $view->esc($field->getInfoText(action: $actionName, below: true)) ?>
        
        <?php if ($displayAsModal) { ?>
            <div class="modal modal-single-options top" data-modal='{"id": "<?= $view->esc($field->name()) ?>"}'>
                <div class="modal-background"></div>
                <div class="modal-content modal-m">
                    <div class="modal-head crud-select-search">
                        <?= $form->input(
                            name: 'search.'.$field->name(),
                            type: 'search',
                            value: '',
                            attributes: [
                                'class' => 'small fit',
                                'data-single-options-action' => 'search',
                                'aria-label' => $view->trans('Search'),
                                'placeholder' => $field->getPlaceholder()
                            ],
                        ) ?>
                    </div>
                    <div class="modal-body">
                        <div class="crud-select-options" data-unselected="<?= $view->esc($field->name()) ?>">
                            <?php foreach($unselectedOptions as $item) { ?>
                                <?php $option = $field->createOption($item, $view, $field); ?>
                                <div class="crud-select-option" data-single-options-action="add" data-value="<?= $view->esc($option->value()) ?>">
                                    <?= $option->getHtml() ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="modal-foot">
                        <div class="buttons spaced">
                            <span class="link modal-close"><?= $view->etrans('cancel') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        
    </div>
</div>