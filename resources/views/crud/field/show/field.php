<?php if ($field->isTranslatable()) { ?>
    <div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>" data-translatable="1">
        <?php if ($renderLabel) { ?>
            <div class="field-label text-700"><?= $view->etrans($field->label()) ?></div>
        <?php } ?>
        <div class="field-body">
            <?php foreach($field->locales() as $locale => $name) { ?>
                <div class="field">
                    <div class="field-label text-xxs"><?= $view->esc($name) ?></div>
                    <div class="field-body text-body">
                        <?= nl2br($view->esc($entity->get($field->name(), '', $locale))) ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
<?php } else { ?>
    <div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>">
        <?php if ($renderLabel) { ?>
            <div class="field-label text-700"><?= $view->etrans($field->label()) ?></div>
        <?php } ?>
        <div class="field-body text-body">
            <?php
            if ($text) {
                echo nl2br($view->esc($text));
            } else {
                echo nl2br($view->esc($entity->get($field->name(), '')));
            }
            ?>
        </div>
    </div>
<?php } ?>