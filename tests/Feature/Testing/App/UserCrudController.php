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
use Tobento\App\User\UserRepositoryInterface;

class UserCrudController extends AbstractCrudController
{
    public const RESOURCE_NAME = 'users';
    
    public function __construct(
        UserRepositoryInterface $repository
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
            Field\Text::new('id'),
            Field\Text::new('number'),
            Field\Text::new('type'),
            Field\Text::new('username'),
            Field\Text::new('email')->infoText('Email info ...'),
            Field\Text::new('smartphone')
                ->validate('required|string')
                ->requiredText('Required smartphone ...'),
            Field\Text::new('translatable')
                ->validate('alnum')
                ->translatable()
                ->infoText('Translatable info text ...')
                ->optionalText('Optional translatable ...'),
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
            Action\BulkDelete::new(),
            Action\BulkEdit::new(name: 'bulk-edit')->field('smartphone'),
            Action\Index::new('Users'),
            Action\Create::new('New user'),
            Action\Store::new(),
            Action\Edit::new(fn ($entity) => 'Edit User: '.$entity?->get('id')),
            Action\Update::new(),
            Action\Show::new(),
            Action\Delete::new(),
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
            Filter\Columns::new()
                ->open(false),
            Filter\Select::new('category')
                ->group('header'),
            Filter\Select::new('sku-select', 'sku')
                ->group('header')
                //->options(['blue' => 'Blue', 'red' => 'RED']),
                ->options(fn () => ['blue' => 'Blue', 'red' => 'RED']),
            Filter\Input::new('sku-same', 'sku')
                ->type('number')
                ->comparison('>')
                ->group('header'),
            Filter\FieldsSortOrder::new()
                ->only('sku', 'title', 'price'),
                //->except('title', 'price'),
            ...Filter\Fields::new()
               ->group('field')
               //->open(false)
               ->fields($action->fields())
               //->only('sku')
               ->toFilters(),
            Filter\PaginationItemsPerPage::new()
                ->group('footer')
                ->open(false),
            Filter\Pagination::new(),
        ];
        
        return $filters;
    }
}