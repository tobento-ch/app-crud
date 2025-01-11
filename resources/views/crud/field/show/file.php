<div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>">
    <?php if ($field->label()) { ?>
        <div class="field-label"><?= $view->etrans($field->label()) ?></div>
    <?php } ?>
    <div class="field-body">
        <?php foreach($fields as $f) { ?>
            <div class="fields pb-s"><?= $f->render() ?></div>
        <?php } ?>
    </div>
</div>