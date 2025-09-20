<?php
$view->asset('assets/crud/field-items.js')->attr('type', 'module');
$form = $view->form();
?>
<div data-field="<?= $view->esc($field->name()) ?>">
    <?php if ($withoutLabel === false && $field->label()) { ?>
        <div class="mb-xs"><?= $view->esc($field->label()) ?></div>
    <?php } ?>
    <?php if ($field->getInfoText(action: $actionName)) { ?>
        <p class="text-xxs mb-xs"><?= $view->esc($field->getInfoText(action: $actionName)) ?></p>
    <?php } ?>
    <?= $form->getMessage($field->name()); ?>
    <div data-items-items="<?= $view->esc($field->name()) ?>">
        <?php foreach($items as $item) { ?>
            <div class="item mb-l" data-items-item="">
                <div class="item-header p-xxs cols right">
                    <div>
                        <span class="link" data-items-action="move.up"><?= $view->etrans('move up') ?></span> |
                        <span class="link" data-items-action="move.down"><?= $view->etrans('move down') ?></span> |
                        <span class="link" data-items-action="delete"><?= $view->etrans('delete') ?></span>
                    </div>
                </div>
                <div class="item-body<?= $asCard ? ' cards crud-cards' : '' ?>">
                    <?php foreach($item as $f) { ?>
                        <div class="fields py-s"><?= $f->render() ?></div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <div class="mb-m">
            <span class="button" data-items-action="new"><?= $view->esc($field->getAddText()) ?></span>
        </div>

        <template data-items-template="">
            <div class="item mb-l" data-items-item="">
                <div class="item-header p-xxs cols right">
                    <div>
                        <span class="link" data-items-action="move.up"><?= $view->etrans('move up') ?></span> |
                        <span class="link" data-items-action="move.down"><?= $view->etrans('move down') ?></span> |
                        <span class="link" data-items-action="delete"><?= $view->etrans('delete') ?></span>
                    </div>
                </div>
                <div class="item-body<?= $asCard ? ' cards crud-cards' : '' ?>">
                    <?php foreach($templateFields as $tf) { ?>
                        <div class="fields py-s"><?= $tf->render() ?></div>
                    <?php } ?>
                </div>
            </div>
        </template>
    </div>
</div>