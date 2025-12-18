<?php
$attributes->add(name: 'class', value: 'crud-buttons');
?>
<?php if (!$displayAsField) { ?>
    <div data-field="<?= $view->esc($field->name()) ?>"<?= (string)$attributes ?>>
        <div class="buttons spaced my-s">
            <?php foreach($buttons->group('entity') as $button) { ?>
                <?= $button->render($view) ?>
            <?php } ?>
        </div>
    </div>
<?php } else { ?>
    <div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>">
        <div class="field-label"><?= $field->label() ? $view->esc($field->label()) : '' ?></div>
        <div class="field-body">
            <div<?= (string)$attributes ?>>
                <div class="buttons spaced">
                    <?php foreach($buttons->group('entity') as $button) { ?>
                        <?= $button->render($view) ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
<?php } ?>