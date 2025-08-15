<?php
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Table\CrudTableRenderer;

$form = $view->form();
$table = $view->table('index')->withRenderer(new CrudTableRenderer());
$columnsFilter = $filters->byClass(Filter\Columns::class)->first();

if ($columnsFilter) {
    $table = $table->withColumns(['bulk', ...$columnsFilter->columns()]);
}

$sortFilter = $filters->group('heading')->byClass(Filter\FieldsSortOrder::class)->first();
$editableColumnsFilter = $filters->byClass(Filter\EditableColumns::class)->first();

// heading row:
$table->row()
      ->when(!$bulkActions->empty(), function($row): void {
          $row->column('bulk', '<input name="bulks" aria-label="all" type="checkbox">', ['class' => 'bulk-col', 'data-header-col' => 'bulk']);
      })
      ->each($action->fields(), function($row, $field, $key) use ($form, $action, $sortFilter, $view): void {
          if (! $field->isIndexable()) {
              return;
          }
          
          if ($sortFilter?->isSortable($field->name())) {

              $value = $sortFilter->getValueFor($field->name()); // null, 'asc', 'desc'

              if ($value === null) {
                  $sortClass = ' sort';
              } else {
                  $sortClass = $value === 'asc' ? ' active sort-up' : ' active sort-down';
              }
              
              $titleHtml = '<a class="link text-xs text-900'.$sortClass.'" href="'.$view->esc($action->getLinkUrl()).'?filter[resort]='.$view->esc($field->name()).'" data-filter="sort">'.$view->esc($field->label()).'</a>';

              $row->column($field->name(), $titleHtml, ['data-header-col' => $key]);
          } else {
              $row->column($field->name(), $view->esc($field->label()), ['data-header-col' => $key]);
          }
      })
      ->id('heading')
      ->when($buttons->group('entity')->has(), function($row) use ($view): void {
          $row->column('actions', $view->trans('Actions'), ['data-header-col' => 'actions']);
      })
      ->heading()
      ->html(...$action->fields()->getNames())
      ->html('bulk');

// filter row:
if (!$filters->group('field')->empty()) {
    $table->row()
          ->attributes(['data-filters' => 'field'])
          ->when(!$bulkActions->empty(), function($row): void {
              $row->column('bulk', '', ['class' => 'bulk-col']);
          })
          ->each($action->fields(), function($row, $field) use ($filters, $view): void {
              if (! $field->isIndexable()) {
                  return;
              }
              
              $filterHtml = '';
              foreach($filters->field($field->name())->group('field') as $filter) {
                  $filterHtml .= $filter->render($view);
              }
              $row->column($field->name(), $filterHtml);
          })
          ->column('actions', '<button class="button text-xs display-none-if-js">'.$view->etrans('Apply filters').'</button>')
          ->prependHtml($form->form(['action' => $action->getLinkUrl(), 'method' => 'GET', 'data-form-filter' => 'field']))
          ->appendHtml($form->close())        
          ->html('bulk', ...$action->fields()->getNames())
          ->html('actions')
          ->id('filters');
}

// entities rows:
foreach($action->entities() as $entity) {
    $table->row()
          ->attributes(['data-entity-id' => (string)$entity->id()])
          ->when(!$bulkActions->empty(), function($row) use ($entity, $view): void {
              $row->column('bulk', '<input id="entity-'.$view->esc($entity->id()).'" name="bulk[]" aria-label="'.$view->esc($entity->id()).'" type="checkbox" value="'.$view->esc($entity->id()).'">', ['class' => 'bulk-col']);
          })
          ->each($entity->fields()->parent(null), function($row, $field, $key) use ($entity, $locale): void {
              if (! $field->isIndexable()) {
                  return;
              }

              $row->column($field->name(), $field->render(), ['data-field' => $field->name()]);
              $row->html($field->name());
          })
          ->when($entity->buttons()->has(), function($row) use ($buttons, $entity, $view): void {

              $html = '<div class="buttons spaced">';
              
              foreach($entity->buttons() as $button) {
                  $html .= $button->render($view);
              }
              
              $html .= '</div>';
              
              $row->column('actions', $html, ['class' => 'overflow-visible']);
          })
          ->html('bulk')
          ->html('actions');
}
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
            
            <?= $table ?>
            
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