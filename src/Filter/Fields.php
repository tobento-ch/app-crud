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
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\Collection\Arr;

/**
 * Fields filter factory
 */
class Fields
{
    /**
     * @var string
     */
    protected string $group = 'field';
    
    /**
     * @var bool
     */
    protected bool $open = true;
    
    /**
     * @var null|FieldsInterface $fields
     */
    protected null|FieldsInterface $fields = null;
    
    /**
     * @var null|array<int, string>
     */
    protected null|array $only = null;
    
    /**
     * @var null|array<int, string>
     */
    protected null|array $except = null;
    
    /**
     * Create a new Fields.
     */
    final public function __construct()
    {
        //
    }
    
    /**
     * Create a new instance.
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }
    
    /**
     * Sets the group.
     *
     * @param string $group
     * @return static $this
     */
    public function group(string $group): static
    {
        $this->group = $group;
        return $this;
    }
    
    /**
     * Sets if the filter is open.
     *
     * @param bool $open
     * @return static $this
     */
    public function open(bool $open = true): static
    {
        $this->open = $open;
        return $this;
    }

    /**
     * Sets the fields.
     *
     * @param FieldsInterface $fields
     * @return static $this
     */
    public function fields(FieldsInterface $fields): static
    {
        $this->fields = $fields;
        return $this;
    }
    
    /**
     * Returns the fields.
     *
     * @return FieldsInterface
     */
    public function getFields(): FieldsInterface
    {
        return $this->fields ?: new Field\Fields();
    }
    
    /**
     * Sets the field names to create filters only.
     *
     * @param string ...$name
     * @return static $this
     */
    public function only(string ...$name): static
    {
        $this->only = $name;
        return $this;
    }
    
    /**
     * Sets the field names to be excluded.
     *
     * @param string ...$name
     * @return static $this
     */
    public function except(string ...$name): static
    {
        $this->except = $name;
        return $this;
    }
    
    /**
     * Returns the created filters.
     *
     * @return array<int, FilterInterface>
     */
    public function toFilters(): array
    {
        $fieldNames = $this->getFields()->getNames();
        
        if ($this->only !== null) {
            $fieldNames = array_keys(Arr::onlyPresent(array_flip($fieldNames), $this->only));
            $this->only = null;
        }

        if ($this->except !== null) {
            $fieldNames = array_flip(Arr::except(array_flip($fieldNames), $this->except));
            $this->except = null;
        }
        
        $filters = [];
        
        foreach($fieldNames as $name) {
            $field = $this->getFields()->get($name);
            
            if (is_null($field)) {
                continue;
            }
            
            if ($filter = $this->createFilterForField($field)) {
                $filters[] = $filter;
            }
        }
        
        return $filters;
    }
    
    /**
     * Returns the created filter for the given field.
     *
     * @param FieldInterface $field
     * @return null|FilterInterface
     */
    protected function createFilterForField(FieldInterface $field): null|FilterInterface
    {
        $name = $field->name();
        $label = $field->label();

        if (
            ($field instanceof Field\Select && !$field->isMultipleSelection())
            || $field instanceof Field\Radios
        ) {
            return Select::new(name: 'field.'.$name, field: $name)
                ->group($this->group)
                ->options($field->getOptions())
                ->comparison('=')
                ->attributes(['aria-label' => $label])
                ->open($this->open);
        }
        
        return Input::new(name: 'field.'.$name, field: $name)
            ->group($this->group)
            ->type('search')
            ->comparison('like')
            ->attributes(['aria-label' => $label])
            ->open($this->open);
    }
}