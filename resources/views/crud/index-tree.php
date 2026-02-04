<?php
use Tobento\App\Crud\Filter;
use Tobento\Service\Treeable\ArrayTree;
use Tobento\Service\Treeable\Traverser;

$bulkTreeAction = $bulkActions->get('bulk-tree-update');
$parentIdName = $bulkTreeAction?->parentIdName();

$editableColumnsFilter = $filters->byClass(Filter\EditableColumns::class)->first();
$columnsFilter = $filters->byClass(Filter\Columns::class)->first();

if ($columnsFilter) {
    $columnsFilter->columns();
}

$form = $view->form();

$entities = $action
    ->entities()
    ->map(function($entity) use ($view, $columnsFilter, $parentIdName): array {
        $html = '<div class="crud-tree-item td" data-entity-id="'.$view->esc($entity->id()).'">';
        
        $html .= '<div class="crud-tree-grab-col crud-drag link">'.$view->icon('grip-vertical').'</div>';
        
        foreach($entity->fields()->parent(null) as $field) {
            if (!$field->isIndexable()) {
                continue;
            }
            
            if ($columnsFilter && !in_array($field->name(), $columnsFilter->columns())) {
                continue;
            }
            
            $html .= '<div class="crud-tree-item-col" data-field="'.$view->esc($field->name()).'">'.$field->render().'</div>';
        }
        
        if (
            is_null($columnsFilter)
            || ($columnsFilter && in_array('actions', $columnsFilter->columns()))
        ) {
            if ($entity->buttons()->has()) {
                $html .= '<div class="crud-tree-item-col"><div class="buttons spaced" data-buttons="entity">';

                foreach($entity->buttons() as $button) {
                    $html .= $button->render($view);
                }

                $html .= '</div></div>';            
            }
        }
        
        $html .= '</div>';
        
        $pId = $entity->get($parentIdName ?: 'parent_id');
        $pId = empty($pId) ? null : $pId;
        return ['id' => $entity->id(), 'parent' => $pId, 'html' => $html];
    });

$tree = (new ArrayTree($entities->all(), 'id', 'parent'))->create();

// change the children key if needed.
$entitiesTreeHtml = (new Traverser($tree, 'children'))
    ->before(function($level) {
        return $level === 0 ? '<ul class="crud-tree">' : '<ul>';
    })
    ->item(function($item, $childrenHtml, $level) use ($view) {
        
        if (!empty($childrenHtml)) {
            return '<li data-id="'.$view->esc($item['id']).'">'.$item['html'].$childrenHtml.'</li>';
        }
        
        return '<li data-id="'.$view->esc($item['id']).'">'.$item['html'].'</li>';
    })
    ->after(function($level) {
        return '</ul>';
    })
    ->render();

// Heading:
$headingHtml = '<div data-filters="field" class="crud-tree-item tr">';
$headingHtml .= '<div class="crud-tree-grab-col"></div>';

foreach($action->fields() as $field) {
    if (! $field->isIndexable()) {
        continue;
    }

    if ($columnsFilter && !in_array($field->name(), $columnsFilter->columns())) {
        continue;
    }

    $headingHtml .= '<div class="crud-tree-item-col title">'.$view->esc($field->label()).'</div>';
}

if (
    is_null($columnsFilter)
    || ($columnsFilter && in_array('actions', $columnsFilter->columns()))
) {
    $headingHtml .= '<div class="crud-tree-item-col"><div class="title mb-xs">'.$view->etrans('Actions').'</div></div>';
}


$headingHtml .= '</div>';
?>
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
        $view->asset('assets/css/table.css');
        $view->asset('assets/crud/crud.css');
        $view->asset('assets/crud/confirm.js')->attr('type', 'module');
        $view->asset('assets/crud/dropdowns.js')->attr('async');
        $view->asset('assets/crud/button.js')->attr('type', 'module');
        $view->asset('assets/crud/index-action.js')->attr('type', 'module');
        if ($editableColumnsFilter) {
            $view->asset('assets/crud/index-action-table.js')->attr('type', 'module');
        }
        $view->asset('assets/modal/modals.css');
        $view->asset('assets/modal/modals.js')->attr('type', 'module');
        $view->asset('assets/js-notifier/notifier.css');
        ?>
    </head>
    
    <?php
    $bodyAttributes = $view->tagAttributes('body')
        ->add('class', $filters->group('aside')->empty() ? 'page' : 'page-asided')
        ->add('class', 'crud');
    
    if ($editableColumnsFilter) {
        $bodyAttributes->add('data-update-url', (string)$buttons->get('update')?->getUrl());
    }
    ?>
    <body<?= $bodyAttributes ?>>

        <?= $view->render('inc/header') ?>
        <?= $view->render('inc/nav') ?>
        
        <?php if (!$filters->group('aside')->empty()) { ?>
            <aside class="page-aside">
                <?= $form->form(['action' => $action->getLinkUrl(), 'method' => 'GET', 'data-form-filter' => 'aside']) ?>
                <div class="text-xs" id="filters-aside" data-filters="aside">
                    <?php if (! $filters->group('aside')->open(false)->empty()) { ?>
                        <div class="closed-reversed field small">
                            <a href="#filters-aside"><?= $filters->group('aside')->open(true)->empty() ? $view->etrans('Filters') : $view->etrans('More Filters') ?></a>
                        </div>
                        <div class="closed field small">
                            <a href="#"><?= $view->etrans('Less Filters') ?></a>
                        </div>
                    <?php } ?>
                    <?php foreach($filters->group('aside') as $filter) { ?>
                        <?= $filter->render($view) ?>
                    <?php } ?>
                    <div class="field small display-none-if-js">
                        <?= $form->button(text: $view->trans('Apply filters'), attributes: ['class' => 'button raw fit text-xs']) ?>
                    </div>
                </div>
                <?= $form->close() ?>
            </aside>
        <?php } ?>
        
        <main class="page-main">

            <?= $view->render('inc.breadcrumb') ?>
            <?= $view->render('inc.messages') ?>

            <h1 class="text-xl"><?= $view->esc($action->title()) ?></h1>
                
            <div class="controls buttons spaced pt-xs" data-buttons="global">
                <?php foreach($buttons->group('global') as $button) { ?>
                    <?= $button->render($view) ?>
                <?php } ?>
            </div>
            
            <?php if (!$filters->group('header')->empty()) { ?>
                <?= $form->form(['action' => $action->getLinkUrl(), 'method' => 'GET', 'data-form-filter' => 'header']) ?>
                <div class="filters text-xs" id="filters-header" data-filters="header">
                    <?php if (! $filters->group('header')->open(false)->empty()) { ?>
                        <div class="closed-reversed field small">
                            <a href="#filters-header"><?= $filters->group('header')->open(true)->empty() ? $view->etrans('Filters') : $view->etrans('More Filters') ?></a>
                        </div>
                        <div class="closed field small">
                            <a href="#"><?= $view->etrans('Less Filters') ?></a>
                        </div>
                    <?php } ?>
                    <?php foreach($filters->group('header') as $filter) { ?>
                        <?= $filter->render($view) ?>
                    <?php } ?>
                    <div class="field small display-none-if-js">
                        <?= $form->button(text: $view->trans('Apply filters'), attributes: ['class' => 'button raw fit text-xs']) ?>
                    </div>
                </div>
                <?= $form->close() ?>
            <?php } ?>
            
            <div data-table-group="heading"><?= $headingHtml ?></div>
            <div data-table-group="filters"></div>
            <div data-table-group="items" data-tree-form="">
                <?= $entitiesTreeHtml ?>
            </div>
            
            <?php if ($action->entities()->empty()) { ?>
                <div data-table-group="items">
                    <div><p class="no-records text-body"><?= $view->etrans('No records found.') ?></p></div>
                </div>
            <?php } ?>
            
            <?php if (!$filters->group('footer')->empty()) { ?>
                <?= $form->form(['action' => $action->getLinkUrl(), 'method' => 'GET', 'data-form-filter' => 'footer']) ?>
                <div class="filters text-xs mt-s" id="filters-footer" data-filters="footer">
                    <?php if (! $filters->group('footer')->open(false)->empty()) { ?>
                        <div class="closed-reversed field small">
                            <a href="#filters-footer"><?= $filters->group('footer')->open(true)->empty() ? $view->etrans('Filters') : $view->etrans('More Filters') ?></a>
                        </div>
                        <div class="closed field small">
                            <a href="#"><?= $view->etrans('Less Filters') ?></a>
                        </div>
                    <?php } ?>
                    <?php foreach($filters->group('footer') as $filter) { ?>
                        <?= $filter->render($view) ?>
                    <?php } ?>
                    <div class="field small display-none-if-js">
                        <?= $form->button(text: $view->trans('Apply filters'), attributes: ['class' => 'button raw fit text-xs']) ?>
                    </div>
                </div>
                <?= $form->close() ?>
            <?php } ?>
            
            <?php if (!$filters->group('modal')->empty()) { ?>
                <div class="modal modal-fade top right" data-modal='{"id": "filters"}'>
                    <div class="modal-background"></div>
                    <div class="modal-content modal-m">
                        <div class="modal-head buttons spaced-between">
                            <span class="link"><a href="?clear-filter=1" data-filter="modal.clear"><?= $view->etrans('Clear Filters') ?></a></span>
                            <span class="link modal-close"><?= $view->etrans('close') ?></span>
                        </div>
                        <div class="modal-body">
                            <?= $form->form(['action' => $action->getLinkUrl(), 'method' => 'GET', 'data-form-filter' => 'modal']) ?>
                            <div class="text-xs" id="filters-modal" data-filters="modal">
                                <?php foreach($filters->group('modal') as $filter) { ?>
                                    <?= $filter->render($view) ?>
                                <?php } ?>
                                <div class="field small display-none-if-js">
                                    <?= $form->button(text: $view->trans('Apply filters'), attributes: ['class' => 'button raw fit text-xs']) ?>
                                </div>
                            </div>
                            <?= $form->close() ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
            
            <?php if (!$bulkActions->empty()) { ?>
                <div class="bulks">
                    <?php foreach($bulkActions as $bulkAction) { ?>
                        <?= $bulkAction->render($view) ?>
                    <?php } ?>
                </div>
            <?php } ?>
            <?php
            $bulkActions = $bulkActions->filter(fn ($a) => $a->displayButton());
            if (!$bulkActions->empty()) { ?>
                <div class="crud-dropdown display-none" data-dropdown="bulk">
                    <button class="button text-xs"><?= $view->etrans('Actions') ?></button>
                    <div class="crud-dropdown-menu">
                        <div class="crud-dropdown-body">
                        <?php foreach ($bulkActions as $bulkAction) { ?>
                            <div class="crud-dropdown-item">
                                <button class="button raw text-xs" data-bulk-action="<?= $view->esc($bulkAction->name()) ?>"><?= $view->esc($bulkAction->title()) ?></button>
                            </div>
                        <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
            
            <?= $view->render('crud/modal/confirm') ?>
        </main>
        
        <?= $view->render('inc/footer') ?>
    </body>
</html>