<?php $form = $view->form(); ?>
<?php if ($field->isTranslatable()) { ?>        
	<div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>" data-translatable="1">
        <div class="field-label">
            <?= $form->label(
                text: $view->trans($field->label()),
                for: null,
                requiredText: $view->trans($field->getRequiredText(action: $actionName)),
                optionalText: $view->trans($field->getOptionalText(action: $actionName)),
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
                        <?= $form->input(
                            name: $field->name().'.'.$locale,
                            type: $inputType,
                            value: $field->getValue($field, $locale),
                            attributes: $inputAttributes,
                        ) ?>
                        <?php if ($field->getInfoText(action: $actionName)) { ?>
                            <p class="text-xxs"><?= $view->etrans($field->getInfoText(action: $actionName)) ?></p>
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
                text: $view->trans($field->label()),
                for: $field->name(),
                requiredText: $view->trans($field->getRequiredText(action: $actionName)),
                optionalText: $view->trans($field->getOptionalText(action: $actionName)),
            ) ?>
        </div>
		<div class="field-body">
            <?= $form->input(
                name: $field->name(),
                type: $inputType,
                value: $field->getValue($field),
                attributes: $inputAttributes,
            ) ?>
            <?php if ($field->getInfoText(action: $actionName)) { ?>
                <p class="text-xxs"><?= $view->etrans($field->getInfoText(action: $actionName)) ?></p>
            <?php } ?>
		</div>
	</div>
<?php } ?>