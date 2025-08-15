<?php
$view->asset('assets/crud/index-tree.js')->attr('type', 'module');
?>
<div 
    class="display-none"
    data-tree-update-url="<?= $view->esc($action->getUrl()) ?>"
    data-tree-success-message="<?= $view->etrans('Tree structure successfully updated.') ?>"
    data-tree-error-message="<?= $view->etrans('Tree structure partially updated.') ?>"
></div>