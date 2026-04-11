<?php $form = $view->form(); ?>
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
                    <div class="text-xxs mb-xxs">
                        <?= $form->label(
                            text: $name,
                            for: $field->name().'.'.$locale,
                        ) ?>
                        <?php if ($field->hasMachineTranslator()) { ?>
                            <?= $field->renderMachineTranslator(toField: $field->name().'.'.$locale, view: $view) ?>
                        <?php } ?>
                    </div>
                    <div class="field-body">
                        <?= $view->esc($field->getInfoText(action: $actionName, below: false)) ?>
                        <?= $form->textarea(
                            name: $field->name().'.'.$locale,
                            value: $entity->get($field->name(), '', $locale),
                            attributes: $attributes,
                        ) ?>
                        <?= $view->esc($field->getInfoText(action: $actionName, below: true)) ?>
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
                for: $field->name(),
                requiredText: $field->getRequiredText(action: $actionName),
                optionalText: $field->getOptionalText(action: $actionName),
            ) ?>
        </div>
        <div class="field-body">
            <?= $view->esc($field->getInfoText(action: $actionName, below: false)) ?>
            <?= $form->textarea(
                name: $field->name(),
                value: $entity->get($field->name(), ''),
                attributes: $attributes,
            ) ?>
            <?= $view->esc($field->getInfoText(action: $actionName, below: true)) ?>
        </div>
    </div>
<?php } ?>