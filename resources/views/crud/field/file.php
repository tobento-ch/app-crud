<?php
$view->asset('assets/crud/field-file.js')->attr('type', 'module');

$form = $view->form();
?>
<div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>">
    <?php if ($field->label()) { ?>
        <div class="field-label">
            <?= $form->label(
                text: $view->trans($field->label()),
                for: $field->isTranslatable()
                    ? $field->name().'.src.'.$field->locale()
                    : $field->name().'.src',
                requiredText: $view->trans($field->getRequiredText(action: $actionName)),
                optionalText: $view->trans($field->getOptionalText(action: $actionName)),
            ) ?>
        </div>
    <?php } ?>
    <div class="field-body">
        <?php if ($file) { ?>
            <div data-file-file="<?= $view->esc($field->name()) ?>">
                <div class="cols">
                    <?php if ($picture) { ?>
                        <div class="col-2 pr-m"><?= $picture ?></div>
                    <?php } ?>
                    <div class="col-10">
                        <div class="mb-xxs"><?= $view->esc($file->path()) ?></div>
                        <div class="mb-xxs"><?= $view->esc($file->humanSize()) ?></div>
                        <div class="file-actions">
                            <span class="link" data-action="file-delete"><?= $view->etrans('delete') ?></span>
                            <?php if ($field->isOrderable()) { ?>
                                <span class="link" data-action="move.up"><?= $view->etrans('move up') ?></span>
                                <span class="link" data-action="move.down"><?= $view->etrans('move down') ?></span>
                            <?php } ?>
                            <span class="link" data-action="file-edit"><?= $view->etrans('edit') ?></span>
                        </div>
                    </div>
                </div>
                <div class="mt-s display-none" data-file-edit="">
                    <?php foreach($fields as $f) { ?>
                        <div class="fields pb-s"><?= $f->render() ?></div>
                    <?php } ?>
                </div>
            </div>
        <?php } else { ?>
            <?php foreach($fields as $f) { ?>
                <div class="fields pb-s"><?= $f->render() ?></div>
            <?php } ?>
        <?php } ?>
    </div>
</div>