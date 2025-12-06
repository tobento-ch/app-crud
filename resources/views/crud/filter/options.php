<?php
$view->asset('assets/crud/filter-options.js')->attr('type', 'module');
$form = $view->form();
?>
<div class="field small<?= $open ? '' : ' closed' ?>" data-field="<?= $view->esc($name) ?>" data-field-type="single-options">
    <?php if ($label) { ?>
        <div class="field-label">
            <label<?= $labelFor ? ' for="'.$view->esc($labelFor).'"' : '' ?>><?= $view->esc($label) ?></label>
        </div>
    <?php } ?>
    <div class="field-body" data-options="<?= $view->esc($name) ?>">
        <div class="crud-select-input-ctn">
            <button
                type="button"
                class="crud-select-input-btn small min-width-xs"
                data-single-options-action="open-dropdown"
                data-selected="<?= $view->esc($name) ?>"
            >
                <?php if ($selectedOption) { ?>
                    <div class="crud-select-option"><?= $selectedOption->getHtml() ?></div>
                <?php } ?>
            </button>
        </div>
        <div data-filter="<?= $view->esc($name) ?>">
            <?= $form->input(
                name: $inputName,
                type: 'hidden',
                value: (string)$selectedOption?->value(),
                attributes: ['id' => null, 'data-field-input' => ''],
            ) ?>
            
            <div class="modal modal-single-options top" data-modal='{"id": "<?= $view->esc($name) ?>"}'>
                <div class="modal-background"></div>
                <div class="modal-content modal-m">
                    <div class="modal-head crud-select-search">
                        <?= $form->input(
                            name: 'search.'.$name,
                            type: 'search',
                            value: '',
                            attributes: [
                                'class' => 'small fit',
                                'data-single-options-action' => 'search',
                                'aria-label' => $view->trans('Search'),
                                'placeholder' => $filter->getPlaceholder()
                            ],
                        ) ?>
                    </div>
                    <div class="modal-body">
                        <div class="crud-select-options" data-unselected="<?= $view->esc($name) ?>">
                            <?php foreach($unselectedOptions as $item) { ?>
                                <?php $option = $filter->createOption($item, $view, $filter); ?>
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
            
            
        </div>
        <?php if ($description) { ?>
            <p class="mt-xs"><?= $view->esc($description) ?></p>
        <?php } ?>        
    </div>
</div>