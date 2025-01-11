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
use Tobento\Service\View\ViewInterface;
use Tobento\Service\Collection\Arr;

/**
 * FieldsSortOrder
 */
class FieldsSortOrder extends AbstractFilter
{
    /**
     * @var string
     */
    protected string $group = 'heading';
    
    /**
     * @var null|string
     */
    protected null|string $resort = null;
    
    /**
     * @var array<string, null|string>
     */
    protected array $sorted = [];
    
    /**
     * @var array<int, string>
     */
    protected array $sortableFields = [];
    
    /**
     * @var null|array<int, string>
     */
    protected null|array $only = null;
    
    /**
     * @var null|array<int, string>
     */
    protected null|array $except = null;
    
    /**
     * Create a new FieldsSortOrder.
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
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'sort';
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
        $this->sortableFields = $action->fields()->getNames();
        
        // handle the resort for changing sorting state.
        if (is_string($resort = $input->get('resort'))) {
            $this->resort = $resort;
        }
        
        // sort fields:
        if (!is_array($input->get($this->name()))) {
            $input->set($this->name(), []);
        }
        
        $sort = [];
        
        if ($this->resort && !$input->has($this->name().'.'.$this->resort)) {
            $input->set($this->name().'.'.$this->resort, null);
        }        
        
        foreach($input->get($this->name()) as $name => $value) {
            // check if field is sortable:
            if (!$this->isSortable($name)) {
                continue;
            }
            
            // verify value:
            if (!in_array($value, [null, 'asc', 'desc'], true)) {
                continue;
            }
            
            // change state:
            if ($name === $this->resort) {
                $value = $this->rotateSortingValue($value);
            }
            
            if (is_null($value)) {
                continue;
            }
            
            $sort[$name] = $value;
        }
        
        $this->sorted = $sort;
    }
    
    /**
     * Returns true if the field is sortable, otherwise false.
     *
     * @param mixed $name
     * @return bool
     */
    public function isSortable(mixed $name): bool
    {
        if (!is_string($name)) {
            return false;
        }
        
        if ($this->only !== null) {
            $this->sortableFields = array_keys(Arr::only($this->sortableFields, $this->only));
            $this->only = null;
        }

        if ($this->except !== null) {
            $this->sortableFields = array_flip(Arr::except(array_flip($this->sortableFields), $this->except));
            $this->except = null;
        }
        
        return in_array($name, $this->sortableFields) ? true : false;
    }
    
    /**
     * Returns the value for the specified name.
     *
     * @param string $name
     * @return null|string
     */
    public function getValueFor(string $name): null|string
    {
        return $this->sorted[$name] ?? null;
    }
    
    /**
     * Returns the applied parameters.
     *
     * @return array
     */
    public function getAppliedParameters(): array
    {
        return [
            'sort' => $this->sorted,
        ];
    }
    
    /**
     * Returns the order by parameters.
     *
     * @return array
     */
    public function getOrderByParameters(): array
    {
        return $this->sorted;
    }
    
    /**
     * Returns if the filter is active, otherwise false.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return empty($this->sorted) ? false : true;
    }
    
    /**
     * Sets the field names to be sortable only.
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
     * Sets the field names to be excluded from being sortable.
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
     * Returns the where parameters.
     *
     * @return null|string
     */
    protected function rotateSortingValue(null|string $value): null|string
    {
        if ($value === null) {
            return 'asc';
        }
        
        if ($value === 'asc') {
            return 'desc';
        }
        
        if ($value === 'desc') {
            return null;
        }
        
        return null;
    }
}