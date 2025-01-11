<?php if ($field->isTranslatable()) { ?>        
	<div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>" data-translatable="1">
        <div class="field-label text-700"><?= $view->etrans($field->label()) ?></div>
		<div class="field-body">
			<?php foreach($field->locales() as $locale => $name) { ?>
                <div class="field">
                    <div class="field-label text-xxs"><?= $view->esc($name) ?></div>
                    <div class="field-body text-body">
                        <?= $view->esc($entity->get($field->name(), '', $locale)) ?>
                    </div>
                </div>
			<?php } ?>
		</div>
	</div>
<?php } else { ?>
	<div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>">
        <div class="field-label text-700"><?= $view->etrans($field->label()) ?></div>
		<div class="field-body text-body">
            <?php
            $textValue = $entity->get($field->name(), '');
            if ($field->getType() === 'date') {
                echo $view->esc($view->date($textValue));
            } elseif ($field->getType() === 'datetime-local') {
                echo $view->esc($view->dateTime($textValue));
            } else {
                echo $view->esc($textValue);
            }
            ?>
        </div>
	</div>
<?php } ?>