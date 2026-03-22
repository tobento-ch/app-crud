<?php
use Tobento\Service\Tag\Attributes;

$modalAttributes = new Attributes([
    'class' => 'modal',
    'data-modal' => [
        'id' => $action->name(),
    ],
]);
$modalAttributes->add('class', $action->getModalPosition());
$modalAttributes->add('class', $action->getModalAnimation());

$modalContentAttributes = new Attributes(['class' => 'modal-content']);
$modalContentAttributes->add('class', $action->getModalSize());

$form = $view->form();
?>
<div<?= (string)$modalAttributes?>>
    <div class="modal-background"></div>
    <div<?= (string)$modalContentAttributes?>>
        <div class="modal-body" data-bulk-ajax-refresh="<?= $view->esc($action->name()) ?>">
            <?= $form->form([
                'action' => $action->getUrl(),
                'name' => $action->name(),
            ]) ?>
            <?php foreach($action->fields()->column('groupName', 'groupId') as $groupId => $groupName) { ?>
                <section class="fields" data-fields-group="<?= $view->esc($groupId) ?>">
                    <h2 class="group-title"><?= $view->esc($groupName) ?></h2>
                    <?php
                    foreach($action->fields()->group($groupName) as $f) {
                        echo $f->render();
                    }
                    ?>
                </section>
            <?php } ?>
        </div>        
        <div class="modal-foot">
            <div class="buttons spaced">
                <button class="button primary" data-bulk-save="<?= $view->esc($action->name()) ?>"><?= $view->esc($action->getModalButtonLabel()) ?></button>
                <span class="link modal-close"><?= $view->etrans('Cancel') ?></span>
            </div>
        </div>
        <?= $form->close() ?>
    </div>
</div>