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
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\Collection\Arr;
use Tobento\Service\Iterable\Iter;
use Tobento\Service\View\ViewInterface;

/**
 * Radios
 */
class Radios extends AbstractFilter
{
    /**
     * @var null|string
     */
    protected null|string $selected = null;
    
    /**
     * @var null|string
     */
    protected null|string $defaultSelected = null;
    
    /**
     * @var array
     */
    protected array $options = [];
    
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
        $this->selected = null;
        
        if (!$input->has($this->name())) {
            $this->selected = $this->defaultSelected;
            return;
        }

        $selected = $input->get($this->name());
        
        if (
            is_string($selected)
            && $selected !== '_none'
            && array_key_exists($selected, $this->getOptions())
        ) {
            $this->selected = $selected;
        }
    }
    
    /**
     * Returns the applied parameters.
     *
     * @return array
     */
    public function getAppliedParameters(): array
    {
        if ((empty($this->getSelected()) && $this->getSelected() !== '0') || $this->getSelected() === '_none') {
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
        
        if (
            (empty($this->getSelected()) && $this->getSelected() !== '0')
            || $this->getSelected() === '_none'
            || empty($this->fieldName())
        ) {
            return [];
        }
        
        $value = $this->getSelected();
        
        $searchValue = match ($this->comparison) {
            'like' => '%'.(string)$value.'%',
            'not like' => '%'.(string)$value.'%',
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
        
        $body = $form->radios(
            name: $name,
            items: $this->getOptions(),
            selected: $this->getSelected(),
            attributes: $attributes,
            labelAttributes: [],
            withInput: true,
            wrapClass: 'wrap-v',
        );
        
        return $view->render(
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
     * @return null|string
     */
    public function getSelected(): null|string
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
     * Sets the options.
     *
     * @param iterable|callable $options
     * @return static $this
     */
    public function options(iterable|callable $options): static
    {
        if (is_iterable($options)) {
            $this->options = Iter::toArray(iterable: $options);
            return $this;
        }
        
        $this->before(new Resolve($options, function(mixed $resolved): void {
            if (is_iterable($resolved)) {
                $this->options = Iter::toArray(iterable: $resolved);
            }
        }));
        
        return $this;
    }
    
    /**
     * Returns the options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}