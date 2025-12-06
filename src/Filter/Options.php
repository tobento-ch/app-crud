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

namespace Tobento\App\Crud\Filter;

use LogicException;
use Psr\Container\ContainerInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\Option;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\Collection\Arr;
use Tobento\Service\HelperFunction\Functions;
use Tobento\Service\Iterable\Iter;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\View\ViewInterface;

class Options extends AbstractFilter
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
     * @var null|string
     */
    protected null|string $searchValue = null;
    
    /**
     * @var string
     */
    protected string $selected = '';
    
    /**
     * @var string
     */
    protected string $defaultSelected = '';
    
    /**
     * @var null|object
     */
    protected null|object $selectedOption = null;
    
    /**
     * @var string
     */
    protected string $comparison = '=';
    
    /**
     * @var array
     */
    protected array $validComparison = [
        '=', '!=', '>', '<', '>=', '<=', '<>', '<=>', 'like', 'not like', 'contains',
    ];
    
    /**
     * @var array
     */
    protected array $attributes = [];
    
    /**
     * @var string
     */
    protected string $view = 'crud/filter/options';
    
    /**
     * Create a new Select.
     *
     * @param string $name
     * @param null|string $field
     */
    final public function __construct(
        protected string $name,
        protected null|string $field = null,
    ) {
        if ((bool) preg_match('/^[a-z-_.]+$/u', $name) === false) {
            throw new \InvalidArgumentException(
                sprintf('The name %s must only contain [a-z-_.] characters', $name)
            );
        }
        
        $this->before(function(RequesterInterface $requester) {
            $this->searchValue = null;
            
            if ($requester->input()->has('options-search.'.$this->name())) {
                $this->searchValue = $requester->input()->get('options-search.'.$this->name(), '');
            }
        });
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
    
    /**
     * Returns the field name.
     *
     * @return string
     */
    public function fieldName(): string
    {
        return $this->field ?: '';
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
     * Sets the option factory.
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
     * @param Options $filter
     * @return Option
     */
    public function createOption(object $item, ViewInterface $view, Options $filter): Option
    {
        if (is_callable($this->optionFactory)) {
            $option = call_user_func($this->optionFactory, $item, $view, $filter);
            
            $option->html('<span data-single-options-action="remove" class="crud-select-option-remove-btn button raw"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg></span>');                

            return $option;
        }
        
        throw new LogicException('Map your item to options using the toOption method first');
    }
    
    /**
     * Applies the data to filter.
     *
     * @param InputInterface $input Might come from user input. So be careful.
     * @param FiltersInterface $filters
     * @param ActionInterface $action
     * @return void
     */
    public function apply(InputInterface $input, FiltersInterface $filters, ActionInterface $action): void
    {
        $this->selected = '';
        $this->selectedOption = null;
        
        if (!$input->has($this->name())) {
            $this->selected = $this->defaultSelected;
            return;
        }
        
        $selected = $input->get($this->name());
        
        if (is_string($selected) && $selected !== '' && !is_null($option = $this->getSelectedOption($selected))) {
            $this->selected = $selected;
            $this->selectedOption = $option;
        }
    }
    
    /**
     * Returns the applied parameters.
     *
     * @return array
     */
    public function getAppliedParameters(): array
    {
        if (empty($this->getSelected()) && $this->getSelected() !== '0') {
            return [];
        }
        
        return (array) Arr::set([], $this->name(), $this->getSelected());
    }

    /**
     * Returns the where parameters.
     *
     * @return array
     */
    public function getWhereParameters(): array
    {
        if (is_array($this->whereParameters)) {
            return $this->whereParameters;
        }
        
        if ((empty($this->getSelected()) && $this->getSelected() !== '0') || empty($this->fieldName())) {
            return [];
        }

        $value = $this->getSelected();
        
        $searchValue = match ($this->comparison) {
            'like' => '%'.$value.'%',
            'not like' => '%'.$value.'%',
            default => $value,
        };

        return [
            $this->fieldName() => [$this->comparison => $searchValue],
        ];
    }
    
    /**
     * Returns if the filter is active, otherwise false.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return (empty($this->getSelected()) && $this->getSelected() !== '0') || empty($this->fieldName()) ? false : true;
    }

    /**
     * Returns the rendered filter.
     *
     * @param ViewInterface $view
     * @return string
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function render(ViewInterface $view): string
    {
        $form = $view->form();
        $attributes = $this->getAttributes();
        $attributes['id'] ??= $form->nameToId('filter.'.$this->name());
        $name = $form->nameToArray('filter.'.$this->name());
        
        $selectedOption = $this->selectedOption;
        
        if ($selectedOption) {
            $selectedOption = $this->createOption(item: $selectedOption, view: $view, filter: $this);
        }

        return $view->render(
            view: $this->view,
            data: [
                'inputName' => $name,
                'name' => $this->name(),
                'label' => $this->label,
                'labelFor' => $this->label ? $attributes['id'] : '',
                'attributes' => $attributes,
                'description' => $this->description,
                'open' => $this->isOpen(),
                'filter' => $this,
                'selectedOption' => $selectedOption,
                'unselectedOptions' => $this->fetchOptions($this->getSelected(), $this->searchValue),
            ],
        );
    }
    
    /**
     * Sets the attributes.
     *
     * @param array $attributes
     * @return static $this
     */
    public function attributes(array $attributes): static
    {
        $this->attributes = $attributes;
        return $this;
    }
    
    /**
     * Returns the attributes.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    
    /**
     * Sets the selected.
     *
     * @param string $value
     * @return static $this
     */
    public function selected(string $value): static
    {
        $this->defaultSelected = $value;
        
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
     * Sets the comparison.
     *
     * @param string $comparison
     * @return static $this
     */
    public function comparison(string $comparison): static
    {
        if (in_array($comparison, $this->validComparison)) {
            $this->comparison = $comparison;
        }
        
        return $this;
    }
    
    /**
     * Returns the comparison.
     *
     * @return string
     */
    public function getComparison(): string
    {
        return $this->comparison;
    }
    
    /**
     * Returns the options.
     *
     * @param null|string $selected
     * @param null|string $search
     * @return iterable
     */
    public function fetchOptions(null|string $selected, null|string $search = null): iterable
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
}