<?php if (!$displayAsField) { ?>
    <div data-field="<?= $view->esc($field->name()) ?>">
        <?php if ($displayLabel && $field->label()) { ?>
            <div class="mb-xs"><?= $view->esc($field->label()) ?></div>
        <?php } ?>
        <?= $view->esc($field->getInfoText(action: $actionName, below: false)) ?>
        <?= $view->esc($field->getInfoText(action: $actionName, below: true)) ?>
        <div class="<?= $asCard ? 'cards crud-cards' : '' ?>">
            <?php foreach($fields as $f) { ?>
                <div class="fields pb-s"><?= $f->render() ?></div>
            <?php } ?>
        </div>
    </div>
<?php } else { ?>
    <div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>">
        <div class="field-label">
            <?php if ($displayLabel && $field->label()) { ?>
                <div class="mb-xs"><?= $view->esc($field->label()) ?></div>
            <?php } ?>
            <?= $view->esc($field->getInfoText(action: $actionName, below: false)) ?>
            <?= $view->esc($field->getInfoText(action: $actionName, below: true)) ?>
        </div>
        <div class="field-body">
            <div class="<?= $asCard ? 'cards crud-cards' : '' ?>">
                <?php foreach($fields as $f) { ?>
                    <div class="fields pb-s"><?= $f->render() ?></div>
                <?php } ?>
            </div>
        </div>
    </div>
<?php } ?>