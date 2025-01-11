<?php
$form = $view->form();

if ($field->isTranslatable()) {
    foreach($field->locales() as $locale => $name) {
        echo $form->input(
            name: $field->name().'.'.$locale,
            type: $inputType,
            value: $entity->get($field->name(), '', $locale),
            attributes: $inputAttributes,
        );
    }
} else {
    echo $form->input(
        name: $field->name(),
        type: $inputType,
        value: $entity->get($field->name(), ''),
        attributes: $inputAttributes,
    );
}
?>