<div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>">
    <div class="field-label"><?= $view->esc($field->label()) ?></div>
    <div class="field-body">
        <?php if (!empty($files)) { ?>
            <?php foreach($files as $file) { ?>
                <div class="mb-s"><?= $file->render() ?></div>
            <?php } ?>
        <?php }?>
    </div>
</div>