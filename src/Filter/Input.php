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

use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\Collection\Arr;
use Tobento\Service\View\ViewInterface;

/**
 * Input
 */
class Input extends AbstractFilter
{
    /**
     * @var null|string
     */
    protected null|string $searchValue = null;

    /**
     * @var string
     */
    protected string $type = 'text';
    
    /**
     * @var array
     */
    protected array $attributes = [];
    
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
     * Create a new Input.
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
    }

    /**
     * Create a new instance.
     *
     * @param string $name
     * @param null|string $field
     * @return static
     */
    public static function new(string $name, null|string $field = null): static
    {
        return new static($name, $field);
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
     * Applies the data to filter.
     *
     * @param InputInterface $input Might come from user input. So be careful.
     * @param FiltersInterface $filters
     * @param ActionInterface $action
     * @return void
     */
    public function apply(InputInterface $input, FiltersInterface $filters, ActionInterface $action): void
    {
        $this->searchValue = null;
        
        if (! $input->has($this->name())) {
            return;
        }
        
        $searchValue = $input->get($this->name());
        
        if (is_string($searchValue) && $searchValue !== '') {
            $this->searchValue = $searchValue;
        }
    }
    
    /**
     * Returns the search value.
     *
     * @return string
     */
    public function getSearchValue(): null|string
    {
        return $this->searchValue;
    }
    
    /**
     * Returns the applied parameters.
     *
     * @return array
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    public function getAppliedParameters(): array
    {
        if (is_null($this->searchValue)) {
            return [];
        }
        
        return Arr::set([], $this->name(), $this->searchValue);
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
        
        if (is_null($this->searchValue) || empty($this->fieldName())) {
            return [];
        }
        
        $searchValue = match ($this->comparison) {
            'like' => '%'.$this->searchValue.'%',
            'not like' => '%'.$this->searchValue.'%',
            default => $this->searchValue,
        };
        
        // we could check if field exists. But on repository storage
        // it will just be ignored anyway. On custom repository it might throw.
        
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
        return is_null($this->searchValue) || empty($this->fieldName()) ? false : true;
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
        
        if (empty($this->label) && !isset($attributes['aria-label'])) {
            $attributes['aria-label'] = $this->name();
        }
        
        $body = $form->input(
            name: $form->nameToArray('filter.'.$this->name()),
            type: $this->type,
            value: $this->searchValue,
            attributes: $attributes,
            selected: null,
            withInput: true,
        );
        
        $html = $view->render(
            view: $this->view,
            data: [
                'name' => $this->name(),
                'label' => $this->label,
                'labelFor' => $this->label ? $attributes['id'] : '',
                'body' => $body, // must be escaped!
                'description' => $this->description,
                'open' => $this->isOpen(),
                'filter' => $this,
            ],
        );
        
        return $html;
    }

    /**
     * Sets the type.
     *
     * @param string $type
     * @return static $this
     */
    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }
    
    /**
     * Returns the type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
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
}