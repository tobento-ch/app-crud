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
            $view->menu('breadcrumb')->item($view->trans('Show'))->order(10000);

            echo $view->render('inc.breadcrumb', [
                'parentMenuId' => $action->controller()->resourceName().'.index'
            ]);
            ?>
            <?= $view->render('inc.messages') ?>

            <h1 class="text-xl"><?= $view->esc($action->title()) ?></h1>
            
            <div class="sticky-controls buttons spaced pt-xs mb-xs">
                <?php foreach($action->buttons()->group('entity') as $button) { ?>
                    <?= $button->render($view) ?>
                <?php } ?>
            </div>
            
            <?php foreach($action->fields()->column('groupName', 'groupId') as $groupId => $groupName) { ?>
                <section class="fields">
                    <a class="fragment" id="<?= $view->esc($groupId) ?>"></a>
                    <h2 class="group-title"><?= $view->esc($groupName) ?></h2>
                    <?php
                    foreach($action->fields()->group($groupName) as $field) {
                        echo $field->render();
                    }
                    ?>
                </section>
            <?php } ?>
        </main>

        <?= $view->render('inc/footer') ?>
    </body>
</html>