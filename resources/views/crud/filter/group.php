<div class="filter-group">
    <details<?= $open ? ' open' : '' ?>>
        <summary class="link text-s"><?= $view->esc($label) ?></summary>
        <div class="filter-group-body">
            <?php if ($description) { ?>
                <p><?= $view->esc($description) ?></p>
            <?php } ?>
            <?php foreach($filters as $groupFilter) { ?>
                <?= $groupFilter->render($view) ?>
            <?php } ?>
        </div>
    </details>
</div>