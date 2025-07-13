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
use Tobento\Service\Support\Str;
use Tobento\Service\Validation\ValidatorInterface;
use Tobento\Service\Validation\Rule\Passes;
use Tobento\Service\View\ViewInterface;

/**
 * Options
 */
class Options extends AbstractField
{
    /**
     * @var null|class-string|RepositoryInterface
     */
    protected null|string|RepositoryInterface $repository = null;

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
    protected string $emptyOption = '_none';
    
    /**
     * @var iterable
     */
    protected iterable $selected = [];
    
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
        $this->process('store:before|update:before', [$this, 'processBeforeSave']);
        $this->process('store|update', [$this, 'processSave']);
        
        // call validate as to add validOptionsRule:
        $this->validate([]);
        
        $this->configure();
    }
    
    /**
     * Create a new instance.
     *
     * @param string $name
     * @param null|string $label
     * @return static
     */
    public static function new(string $name, null|string $label = null): static
    {
        return new static($name, $label);
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
     * @param array $selected
     * @param null|string $search
     * @return iterable
     */
    public function getOptions(array $selected, null|string $search = null): iterable
    {
        $where = [];

        if (!empty($selected) && is_null($search)) {
            $where[$this->storeColumn] = ['not in' => $selected];
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
     * Returns the options.
     *
     * @param array $selected
     * @return iterable
     */
    public function getSelectedOptions(array $selected): iterable
    {
        $items = $this->getRepository()->findAll(where: [
            $this->storeColumn => ['in' => $selected],
        ]);

        return $items;
    }
    
    /**
     * Sets the empty option value.
     *
     * @param string $value
     * @return static $this
     */
    public function emptyOption(string $value): static
    {
        $this->emptyOption = $value;
        return $this;
    }
    
    /**
     * Returns the empty option.
     *
     * @return string
     */
    public function getEmptyOption(): string
    {
        return $this->emptyOption;
    }
    
    /**
     * Sets the selected for the given action(s).
     *
     * @param iterable|callable $value
     * @param null|string $action
     * @return static $this
     */
    public function selected(iterable|callable $value, null|string $action = 'create'): static
    {
        if (is_null($action) && is_iterable($value)) {
            $this->selected = $value;
            return $this;
        }
        
        if (is_iterable($value)) {
            $this->resolve(function (Options $field) use ($value) {
                $field->selected($value, null);
            }, action: $action);
            
            return $this;
        }
        
        $this->resolve(
            resolve: $value,
            resolved: function(Options $field, mixed $resolved): void {
                if (is_iterable($resolved)) {
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
     * @return array
     */
    public function getSelected(): array
    {
        return Iter::toArray(iterable: $this->selected);
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
     * @param Options $field
     * @return Option
     */
    public function createOption(object $item, ViewInterface $view, Options $field): Option
    {
        if (is_callable($this->optionFactory)) {
            return call_user_func($this->optionFactory, $item, $view, $field);
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
        $options = $field->entity()->get($field->name(), []);
        $options = implode(', ', $options);
        $options = mb_strimwidth($options, 0, 100, '...');
        $field->html(Str::esc($options));
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
        $options = $field->entity()->get($field->name(), []);
        $items = $this->getSelectedOptions(selected: $options);
        $texts = [];
        
        foreach($items as $item) {
            $texts[] = strip_tags($this->createOption(item: $item, view: $view, field: $field)->getHtml());
        }
        
        $texts = implode(', ', $texts);

        $field->html($view->render(
            view: 'crud/field/show/field',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'renderLabel' => true,
                'text' => $texts,
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
        $selectedIds = $field->entity()->get($field->name(), $field->getSelected());
        $selectedOptions = $this->getSelectedOptions($selectedIds);
        $searchValue = null;
        
        if ($action->getInput()->has('options-search.'.$field->name())) {
            $searchValue = $action->getInput()->get('options-search.'.$field->name(), '');
        }
        
        $field->html($view->render(
            view: 'crud/field/options',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'selectedOptions' => $selectedOptions,
                'unselectedOptions' => $field->getOptions($selectedIds, $searchValue),
            ],
        ));
    }

    /**
     * Pre processes the store and update action before validation
     * where we remove the empty option value.
     *
     * @param Options $field
     * @param InputInterface $input
     * @return void
     */
    public function processBeforeSave(
        Options $field,
        InputInterface $input,
    ): void {
        if (! $input->has($field->name())) {
            return;
        }
        
        $options = $input->get($field->name());
        
        if (!is_array($options)) {
            $input->delete($field->name());
            return;
        }
        
        $options = (new Collection(array_flip($options)));
        
        $options = $options->except([$field->getEmptyOption()]);

        $input->set($field->name(), array_flip($options->all()));
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
        
        $options = $input->get($field->name());
        
        if (!is_array($options)) {
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
            passes: function(mixed $value): bool {
                if (!is_array($value)) {
                    return false;
                }

                $count = $this->getRepository()->count(where: [
                    $this->storeColumn => ['in' => $value],
                ]);

                return count($value) === $count;
            },
            errorMessage: 'The :attribute items are invalid.',
        );
    }
}