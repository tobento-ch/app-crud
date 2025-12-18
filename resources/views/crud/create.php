<!DOCTYPE html>
<html lang="<?= $view->esc($view->get('htmlLang', 'en')) ?>" class="scroll-behavior-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $view->esc($action->title()) ?></title>
        <meta name="description" content="<?= $view->esc($action->title()) ?>">
        <?= $view->render('inc/head') ?>
        <?= $view->assets()->render() ?>
        <?php
        $view->asset('assets/crud/crud.css');
        $view->asset('assets/crud/confirm.js')->attr('type', 'module');
        $view->asset('assets/crud/dropdowns.js')->attr('async');
        $view->asset('assets/crud/button.js')->attr('type', 'module');
        $view->asset('assets/crud/live.js')->attr('type', 'module');
        $view->asset('assets/modal/modals.css');
        $view->asset('assets/modal/modals.js')->attr('type', 'module');
        $view->asset('assets/js-notifier/notifier.css');
        ?>
    </head>
    
    <body<?= $view->tagAttributes('body')->add('class', 'page-asided crud')->render() ?>>

        <?= $view->render('inc/header') ?>
        <?= $view->render('inc/nav') ?>

        <aside class="page-aside">
            <nav>
            <ul class="menu-v spaced menu-main">
                <?php foreach($action->fields()->column('groupName', 'groupId') as $groupId => $groupName) { ?>
                    <li><a href="#<?= $view->esc($groupId) ?>"><?= $view->esc($groupName) ?></a></li>
                <?php } ?>
            </ul>
            </nav>
        </aside>
        
        <main class="page-main">
            <?php
            $view->menu('breadcrumb')->item($view->trans(ucfirst($action->name())))->order(10000);

            echo $view->render('inc.breadcrumb', [
                'parentMenuId' => $action->controller()->resourceName().'.index'
            ]);
            ?>
            <?= $view->render('inc.messages') ?>

            <h1 class="text-xl"><?= $view->esc($action->title()) ?></h1>
            
            <?php $form = $view->form(); ?>
            
            <?= $form->form([
                'action' => $action->getLinkUrl(),
                'enctype' => 'multipart/form-data',
            ]) ?>
            
            <div class="sticky-controls buttons spaced pt-xs mb-xs">
                <?php foreach($action->buttons()->group('entity') as $button) { ?>
                    <?= $button->render($view) ?>
                <?php } ?>
            </div>
            
            <div data-ajax="refresh">
            <?php foreach($action->fields()->column('groupName', 'groupId') as $groupId => $groupName) { ?>
                <section class="fields" data-fields-group="<?= $view->esc($groupId) ?>">
                    <a class="fragment" id="<?= $view->esc($groupId) ?>"></a>
                    
                    <h2 class="group-title"><?= $view->esc($groupName) ?></h2>

                    <?php foreach($action->fields()->group($groupName) as $field) { ?>
                        <?= $field->render() ?>
                    <?php } ?>
                </section>
            <?php } ?>
            </div>
            
            <?= $form->close() ?>
            
            <?= $view->render('crud/modal/confirm') ?>
        </main>

        <?= $view->render('inc/footer') ?>
    </body>
</html>