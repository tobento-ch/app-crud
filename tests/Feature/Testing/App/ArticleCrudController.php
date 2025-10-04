<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Crud\Test\Feature\Testing\App;

use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter;

class ArticleCrudController extends AbstractCrudController
{
    public const RESOURCE_NAME = 'articles';
    
    public function __construct(
        ArticleRepository $repository
    ) {
        $this->repository = $repository;
    }
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        return [
            new Field\Text('id'),
            new Field\Text('name'),
            new Field\Text('title')->validate('required|htmlclean')->translatable(),
            new Field\Text('desc'),
        ];
    }
    
    /**
     * Returns the configured actions.
     *
     * @return iterable<ActionInterface>|ActionsInterface
     */
    protected function configureActions(): iterable|ActionsInterface
    {
        return [
            new Action\BulkDelete(),
            new Action\BulkEdit(name: 'bulk-edit')->field('smartphone'),
            new Action\Index('Articles'),
            new Action\Create('New Article'),
            new Action\Store(),
            new Action\Edit(fn ($entity) => 'Edit Article: '.$entity?->get('id')),
            new Action\Update(),
            new Action\Show(),
            new Action\Delete(),
        ];
    }
    
    /**
     * Returns the configured filters.
     *
     * @param ActionInterface $action
     * @return iterable<FilterInterface>|FiltersInterface
     */
    protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
    {
        $filters = [
            new Filter\Columns()
                ->open(false),
            new Filter\Select('category')
                ->group('header'),
            new Filter\Select('sku-select', 'status')
                ->group('header')
                //->options(['blue' => 'Blue', 'red' => 'RED']),
                ->options(fn () => ['blue' => 'Blue', 'red' => 'RED']),
            new Filter\Input('sku-same', 'sku')
                ->type('number')
                ->comparison('>')
                ->group('header'),
            new Filter\FieldsSortOrder()
                ->only('status', 'title'),
            ...new Filter\Fields()
               ->group('field')
               //->open(false)
               ->fields($action->fields())
               //->only('sku')
               ->toFilters(),
            new Filter\PaginationItemsPerPage()
                ->group('footer')
                ->open(false),
            new Filter\Pagination(),
        ];
        
        return $filters;
    }
}