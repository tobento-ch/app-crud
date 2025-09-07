<div class="field small<?= $open ? '' : ' closed' ?>" data-filter="<?= $view->esc($name) ?>">
    <?php if ($label) { ?>
        <div class="field-label">
            <label<?= $labelFor ? ' for="'.$view->esc($labelFor).'"' : '' ?>><?= $view->esc($label) ?></label>
        </div>
    <?php } ?>
    <div class="field-body">
        <?= $body ?>
        <?php if ($description) { ?>
            <p class="mt-xs"><?= $view->esc($description) ?></p>
        <?php } ?>
    </div>
</div>