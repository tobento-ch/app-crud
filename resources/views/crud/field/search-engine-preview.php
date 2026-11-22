<?php
use Tobento\Service\Tag\Attributes;

if ($actionName !== 'show') {
    $view->asset('assets/crud/field-search-engine-preview.js')->attr('type', 'module');
}

$form = $view->form();
?>
<div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>">
    <?php if ($actionName === 'show') { ?>
        <div class="field-label text-700"><?= $view->esc($field->label()) ?></div>
    <?php } else { ?>
        <div class="field-label">
            <?= $form->label(
                text: $field->label(),
                for: null,
                requiredText: '',
                optionalText: '',
            ) ?>
        </div>
    <?php } ?>
    <div class="field-body">
        <?= $view->esc($field->getInfoText(action: $actionName, below: false)) ?>
        <div<?= new Attributes($attributes) ?>>
            <?php foreach($previews as $index => $preview) { ?>
                <div<?= $index > 0 ? ' class="mt-s"' : '' ?>>
                    <?php if ($translatable) { ?>
                        <div class="text-xxs mb-xxs" data-search-engine-preview-locale><?= $view->esc($preview['localeName']) ?></div>
                    <?php } ?>
                    <div class="text-700" data-search-engine-preview-title><?= $view->esc($preview['title']) ?></div>
                    <div class="text-success mt-xxs" data-search-engine-preview-url><?= $view->esc($preview['url']) ?></div>
                    <div class="mt-xxs" data-search-engine-preview-description><?= $view->esc($preview['description']) ?></div>
                </div>
            <?php } ?>
        </div>
        <?= $view->esc($field->getInfoText(action: $actionName, below: true)) ?>
    </div>
</div>