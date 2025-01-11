<div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>">
    <div class="field-body">
        <?php if ($file) { ?>
            <div class="cols" data-file-source="<?= $view->esc($field->name()) ?>">
                <?php if ($picture) { ?>
                    <div class="col-2 pr-m"><?= $picture ?></div>
                <?php } ?>
                <div class="col-10">
                    <div class="mb-xxs text-700"><?= $view->esc($file->path()) ?></div>
                    <div class="mb-xxs"><?= $view->esc($file->humanSize()) ?></div>
                    <?php if ($file->width()) { ?>
                        <div class="mb-xxs"><?= $view->esc($file->width()) ?> x <?= $view->esc($file->height()) ?> px</div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>