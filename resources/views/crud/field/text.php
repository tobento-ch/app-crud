<?php
use \Tobento\App\Crud\Html\InputWrap;
use \Tobento\Service\Support\HtmlString;

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
                        <?= $view->esc($field->getInfoText(action: $actionName, below: false)) ?>
                        <?= (string) new InputWrap(
                            prefix: $field->getPrefix(),
                            input: new HtmlString($form->input(
                                name: $field->name().'.'.$locale,
                                type: $inputType,
                                value: $field->getValue($field, $locale, $actionName),
                                attributes: $inputAttributes,
                            )),
                            suffix: $field->getSuffix(),
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
            <?= (string) new InputWrap(
                prefix: $field->getPrefix(),
                prefixAttributes: $field->getPrefixAttributes(),
                input: new HtmlString($form->input(
                    name: $field->name(),
                    type: $inputType,
                    value: $field->getValue($field, null, $actionName),
                    attributes: $inputAttributes,
                )),
                suffix: $field->getSuffix(),
                suffixAttributes: $field->getSuffixAttributes(),
            ) ?>
            <?= $view->esc($field->getInfoText(action: $actionName, below: true)) ?>
        </div>
    </div>
<?php } ?>