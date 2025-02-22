<?php
$form = $view->form();

if ($field->isTranslatable()) {
    foreach($field->locales() as $locale => $name) {
        echo $form->input(
            name: $field->name().'.'.$locale,
            type: $inputType,
            value: $field->getValue($field, $locale),
            attributes: $inputAttributes,
        );
    }
} else {
    echo $form->input(
        name: $field->name(),
        type: $inputType,
        value: $field->getValue($field),
        attributes: $inputAttributes,
    );
}
?>