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

namespace Tobento\App\Crud\Field;

use InvalidArgumentException;
use LogicException;
use Psr\Container\ContainerInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\Iterable\Iter;
use Tobento\Service\Collection\Collection;
use Tobento\Service\HelperFunction\Functions;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Support\HtmlString;
use Tobento\Service\Support\Str;
use Tobento\Service\Validation\ValidatorInterface;
use Tobento\Service\Validation\Rule\Passes;
use Tobento\Service\View\ViewInterface;

/**
 * Allows you to select only a single option.
 */
class SingleOptions extends AbstractField
{
    /**
     * @var null|class-string|RepositoryInterface
     */
    protected null|string|RepositoryInterface $repository = null;

    /**
     * @var array
     */
    protected array $baseWhere = [];
    
    /**
     * @var string
     */
    protected string $storeColumn = 'id';
    
    /**
     * @var array<array-key, string>
     */
    protected array $searchColumns = ['title'];
    
    /**
     * @var int
     */
    protected int $queryLimit = 25;
    
    /**
     * @var string
     */
    protected string $placeholder = '';

    /**
     * @var null|callable
     */
    protected $optionFactory = null;
    
    /**
     * @var string
     */
    protected string $selected = '';
    
    /**
     * @var bool
     */
    protected bool $displayAsModal = false;
    
    /**
     * Create a new Options.
     *
     * @param string $name
     * @param null|string $label
     */
    final public function __construct(
        string $name,
        null|string $label = null,
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->process('index', [$this, 'processIndex']);
        $this->process('show', [$this, 'processShow']);
        $this->process('create|edit|copy', [$this, 'processCreateEdit']);
        $this->process('store|update', [$this, 'processSave']);
        
        // call validate as to add validOptionsRule:
        $this->validate([]);
        
        $this->configure();
    }

    /**
     * Sets the repository.
     *
     * @param class-string|RepositoryInterface $repository
     * @return static $this
     */
    public function repository(string|RepositoryInterface $repository): static
    {
        $this->repository = $repository;
        return $this;
    }
    
    /**
     * Sets the base where parameters for fetching the options.
     *
     * @param array $where
     * @return static $this
     */
    public function baseWhere(array $where): static
    {
        $this->baseWhere = $where;
        return $this;
    }
    
    /**
     * Sets the store column.
     *
     * @param string $column
     * @return static $this
     */
    public function storeColumn(string $column): static
    {
        $this->storeColumn = $column;
        return $this;
    }
    
    /**
     * Sets the search columns.
     *
     * @param string ...$columns
     * @return static $this
     */
    public function searchColumns(string ...$columns): static
    {
        $this->searchColumns = $columns;
        return $this;
    }
    
    /**
     * Sets the limit.
     *
     * @param int $limit
     * @return static $this
     */
    public function limit(int $limit): static
    {
        $this->queryLimit = $limit;
        return $this;
    }
    
    /**
     * Sets the placeholder text.
     *
     * @param string $text
     * @return static $this
     */
    public function placeholder(string $text): static
    {
        $this->placeholder = $text;
        return $this;
    }
    
    /**
     * Returns the placeholder.
     *
     * @return string
     */
    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }
    
    /**
     * Set if to display the search box as modal.
     *
     * @param bool $modal
     * @return static $this
     */
    public function displayAsModal(bool $modal = true): static
    {
        $this->displayAsModal = $modal;
        return $this;
    }
    
    /**
     * Returns the repository.
     *
     * @return RepositoryInterface
     */
    protected function getRepository(): RepositoryInterface
    {
        if (is_string($this->repository)) {
            return $this->repository = Functions::get(ContainerInterface::class)->get($this->repository);
        }
        
        if ($this->repository instanceof RepositoryInterface) {
            return $this->repository;
        }
        
        throw new LogicException('Define a repository using the repository method first');
    }
    
    /**
     * Returns the options.
     *
     * @param null|string $selected
     * @param null|string $search
     * @return iterable
     */
    public function getOptions(null|string $selected, null|string $search = null): iterable
    {
        $where = $this->baseWhere;

        if (!empty($selected) && is_null($search)) {
            $where[$this->storeColumn] = ['not in' => [$selected]];
        }
        
        if ($search !== '') {
            $searches = [];
            $counter = 0;
            
            foreach($this->searchColumns as $column) {
                $counter++;
                if ($counter <= 1) {
                    $searches[$column] = ['like' => '%'.$search.'%'];
                } else {
                    $searches[$column] = ['or like' => '%'.$search.'%'];
                }
            }
            
            if ($counter > 1) {
                $where = $where + [$searches];
            } else {
                $where = $where + $searches;
            }
        }
        
        return $this->getRepository()->findAll(where: $where, limit: $this->queryLimit);
    }
    
    /**
     * Returns the selected option.
     *
     * @param null|string $selected
     * @return null|object
     */
    public function getSelectedOption(null|string $selected): null|object
    {
        if (empty($selected)) {
            return null;
        }
        
        $where = $this->baseWhere;
        $where[$this->storeColumn] = ['=' => $selected];
        
        return $this->getRepository()->findOne(where: $where);
    }
    
    /**
     * Sets the selected for the given action(s).
     *
     * @param string|callable $value
     * @param null|string $action
     * @return static $this
     */
    public function selected(string|callable $value, null|string $action = 'create'): static
    {
        if (is_null($action) && is_string($value)) {
            $this->selected = $value;
            return $this;
        }
        
        if (is_string($value)) {
            $this->resolve(function (SingleOptions $field) use ($value) {
                $field->selected($value, null);
            }, action: $action);
            
            return $this;
        }
        
        $this->resolve(
            resolve: $value,
            resolved: function(SingleOptions $field, mixed $resolved): void {
                if (is_string($resolved)) {
                    $field->selected($resolved, null);
                }
            },
            action: $action,
        );
        
        return $this;
    }
    
    /**
     * Returns the selected.
     *
     * @return string
     */
    public function getSelected(): string
    {
        return $this->selected;
    }
    
    /**
     * Sets the option.
     *
     * @param callable $optionFactory
     * @return static $this
     */
    public function toOption(callable $optionFactory): static
    {
        $this->optionFactory = $optionFactory;
        return $this;
    }
    
    /**
     * Create option from item.
     *
     * @param object $item
     * @param ViewInterface $view
     * @param SingleOptions $field
     * @return Option
     */
    public function createOption(object $item, ViewInterface $view, SingleOptions $field, $action = ''): Option
    {
        if (is_callable($this->optionFactory)) {
            $option = call_user_func($this->optionFactory, $item, $view, $field);
            
            if (!in_array($action, ['show', 'index'])) {
                $option->html('<span data-single-options-action="remove" class="crud-select-option-remove-btn button raw"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg></span>');                
            }

            return $option;
        }
        
        throw new LogicException('Map your item to options using the toOption method first');
    }
    
    /**
     * Set if the attribute is translatable.
     *
     * @param bool $translatable
     * @return static $this
     */
    public function translatable(bool $translatable = true): static
    {
        throw new InvalidArgumentException('Field does not support translations');
    }
    
    /**
     * Set the attribute validate parameters.
     *
     * @param mixed ...$parameters
     * @return static $this
     */
    public function validate(mixed ...$parameters): static
    {
        foreach($parameters as $key => $parameter) {
            $parameters[$key] = !is_array($parameter)
                ? [$parameter, $this->validOptionsRule()]
                : array_merge($parameter, [$this->validOptionsRule()]);
        }
        
        $this->validate = $parameters;
        return $this;
    }
    
    /**
     * Processes the index action.
     *
     * @param FieldInterface $field
     * @return void
     */
    public function processIndex(FieldInterface $field): void
    {
        $option = $field->entity()->get($field->name(), '');
        $field->html(Str::esc($option));
    }
    
    /**
     * Processes the show action.
     *
     * @param FieldInterface $field
     * @param ViewInterface $view
     * @return void
     */
    public function processShow(FieldInterface $field, ViewInterface $view): void
    {
        $option = $field->entity()->get($field->name(), '');
        $text = '';
        
        if ($item = $this->getSelectedOption(selected: $option)) {
            $text = new HtmlString('<div class="crud-select-option">'.$this->createOption(item: $item, view: $view, field: $field, action: 'show')->getHtml().'</div>');
        }
        
        $field->html($view->render(
            view: 'crud/field/show/field',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'renderLabel' => true,
                'text' => $text,
            ],
        ));
    }
        
    /**
     * Processes the create and edit action.
     *
     * @param ActionInterface $action
     * @param FieldInterface $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processCreateEdit(
        ActionInterface $action,
        FieldInterface $field,
        ViewInterface $view,
    ): void {
        $selectedId = $field->entity()->get($field->name(), $field->getSelected());
        $selectedOption = $this->getSelectedOption($selectedId);
        
        if ($selectedOption) {
            $selectedOption = $this->createOption(item: $selectedOption, view: $view, field: $field);
        }
        
        $searchValue = null;
        
        if ($action->getInput()->has('options-search.'.$field->name())) {
            $searchValue = $action->getInput()->get('options-search.'.$field->name(), '');
        }
        
        $field->html($view->render(
            view: 'crud/field/single-options',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'selectedOption' => $selectedOption,
                'unselectedOptions' => $field->getOptions($selectedId, $searchValue),
                'displayAsModal' => $this->displayAsModal,
            ],
        ));
    }
    
    /**
     * Processes the store and update action.
     *
     * @param FieldInterface $field
     * @param InputInterface $input
     * @return void
     */
    public function processSave(
        FieldInterface $field,
        InputInterface $input,
    ): void {
        if (! $input->has($field->name())) {
            return;
        }
        
        $value = $input->get($field->name());
        
        if (!is_string($value)) {
            $input->delete($field->name());
            return;
        }
    }
    
    /**
     * Returns the valid options rule.
     *
     * @return Passes
     */
    protected function validOptionsRule(): Passes
    {
        return new Passes(
            passes: function(string $value): bool {

                $where = $this->baseWhere;
                $where[$this->storeColumn] = ['=' => $value];
                
                $count = $this->getRepository()->count(where: $where);

                return 1 === $count;
            },
            errorMessage: 'The :attribute items are invalid.',
        );
    }
}