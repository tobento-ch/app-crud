<?php if ($field->isTranslatable()) { ?>        
    <div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>" data-translatable="1">
        <div class="field-label text-700"><?= $view->esc($field->label()) ?></div>
        <div class="field-body">
            <?php foreach($field->locales() as $locale => $name) { ?>
                <div class="field">
                    <div class="field-label text-xxs"><?= $view->esc($name) ?></div>
                    <div class="field-body content">
                        <?= $view->sanitizeHtml($entity->get($field->name(), '', $locale)) ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
<?php } else { ?>
    <div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>">
        <div class="field-label text-700"><?= $view->esc($field->label()) ?></div>
        <div class="field-body content">
            <?= $view->sanitizeHtml($entity->get($field->name(), '')) ?>
        </div>
    </div>
<?php } ?>