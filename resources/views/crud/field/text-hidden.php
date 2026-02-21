<?php
$form = $view->form();

if ($field->isTranslatable()) {
    foreach($field->locales() as $locale => $name) {
        echo $form->input(
            name: $field->name().'.'.$locale,
            type: $inputType,
            value: $field->getValue($field, $locale, $actionName),
            attributes: $inputAttributes,
        );
    }
} else {
    echo $form->input(
        name: $field->name(),
        type: $inputType,
        value: $field->getValue($field, null, $actionName),
        attributes: $inputAttributes,
    );
}
?>