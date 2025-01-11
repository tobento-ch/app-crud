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
use Tobento\App\Crud\Field\FieldsInterface;
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
     * @var array<int, string>
     */
    protected array $fieldNames = [];
    
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
        $this->fieldNames = $fields->getNames();
        return $this;
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
        $fieldNames = $this->fieldNames;
        
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
            $filters[] = Input::new(name: 'field.'.$name, field: $name)
                ->group($this->group)
                ->type('search')
                ->comparison('like')
                ->attributes(['aria-label' => $name])
                ->open($this->open);
        }
        
        return $filters;
    }
}