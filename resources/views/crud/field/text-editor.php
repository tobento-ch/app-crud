<?php
$view->asset('assets/js-editor/editor.css');
$view->asset('assets/crud/field-text-editor.js')->attr('type', 'module');

$form = $view->form();
?>
<?php if ($field->isTranslatable()) { ?>        
    <div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>" data-translatable="1">
        <div class="field-label">
            <?= $form->label(
                text: $field->label(),
                for: null,
                requiredText: $field->getRequiredText(action: $actionName),
                optionalText: $field->getOptionalText(action: $actionName),
            ) ?>
        </div>
        <div class="field-body">
            <?php foreach($field->locales() as $locale => $name) { ?>
                <div class="field">
                    <div class="field-label text-xxs">
                        <?= $form->label(
                            text: $name,
                            for: $field->name().'.'.$locale,
                        ) ?>
                    </div>
                    <div class="field-body">
                        <div class="content">
                            <?= $form->textarea(
                                name: $field->name().'.'.$locale,
                                value: $entity->get($field->name(), '', $locale),
                                attributes: $attributes,
                            ) ?>
                        </div>
                        <?php if ($field->getInfoText(action: $actionName)) { ?>
                            <p class="text-xxs mt-xs"><?= $view->esc($field->getInfoText(action: $actionName)) ?></p>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
<?php } else { ?>
    <div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>">
        <div class="field-label">
            <?= $form->label(
                text: $field->label(),
                for: null,
                requiredText: $field->getRequiredText(action: $actionName),
                optionalText: $field->getOptionalText(action: $actionName),
            ) ?>
        </div>
        <div class="field-body">
            <div class="content">
                <?= $form->textarea(
                    name: $field->name(),
                    value: $entity->get($field->name(), ''),
                    attributes: $attributes,
                ) ?>
            </div>
            <?php if ($field->getInfoText(action: $actionName)) { ?>
                <p class="text-xxs mt-xs"><?= $view->esc($field->getInfoText(action: $actionName)) ?></p>
            <?php } ?>
        </div>
    </div>
<?php } ?>