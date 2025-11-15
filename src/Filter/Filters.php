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

use Traversable;
use ArrayIterator;

/**
 * Filters
 */
class Filters implements FiltersInterface
{
    /**
     * @var array<string, FilterInterface>
     */
    protected array $filters = [];
    
    /**
     * Create a new Fields.
     *
     * @param FilterInterface $filters
     */
    public function __construct(
        FilterInterface ...$filters,
    ) {
        foreach($filters as $filter) {
            $this->filters[$filter->name()] = $filter;
        }
    }

    /**
     * Returns the applied parameters.
     *
     * @return array
     */
    public function getAppliedParameters(): array
    {
        $applied = [];
        
        foreach($this as $filter) {
            $applied[] = $filter->getAppliedParameters();
        }
        
        return array_merge_recursive([], ...$applied);
    }
    
    /**
     * Returns the where parameters.
     *
     * @return array
     */
    public function getWhereParameters(): array
    {
        $where = [];
        
        foreach($this as $filter) {
            $where[] = $filter->getWhereParameters();
        }
        
        return array_merge_recursive([], ...$where);
    }
    
    /**
     * Returns the order by parameters.
     *
     * @return array
     */
    public function getOrderByParameters(): array
    {
        $orderBy = [];
        
        foreach($this as $filter) {
            $orderBy[] = $filter->getOrderByParameters();
        }
        
        return array_merge_recursive([], ...$orderBy);
    }
    
    /**
     * Returns the limit parameter.
     *
     * @return array
     */
    public function getLimitParameter(): array
    {
        foreach($this as $filter) {
            $limit = $filter->getLimitParameter();
            
            if (!empty($limit)) {
                return $limit;
            }
        }
        
        return [];
    }
    
    /**
     * Returns a new instance with the filters filtered.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static
    {
        $new = clone $this;
        $new->filters = array_filter($this->filters, $callback);
        return $new;
    }

    /**
     * Returns a new instance with the specified group filtered.
     *
     * @param string $name
     * @return static
     */
    public function group(string $name): static
    {        
        return $this->filter(
            fn(FilterInterface $f): bool => $f->getGroup() === $name
        );
    }
    
    /**
     * Returns a new instance with the specified field filtered.
     *
     * @param string $name
     * @return static
     */
    public function field(string $name): static
    {
        return $this->filter(static function(FilterInterface $f) use ($name): bool {
            $fieldName = $f->fieldName();
            
            if (str_contains($fieldName, '->')) {
                $parts = explode('->', $fieldName);
                $fieldName = implode('.', $parts);
            }
            
            return $fieldName === $name;
        });
    }
    
    /**
     * Returns a new instance with the specified open filtered.
     *
     * @param bool $open
     * @return static
     */
    public function open(bool $open = true): static
    {        
        return $this->filter(
            fn(FilterInterface $f): bool => $f->isOpen() === $open
        );
    }
    
    /**
     * Returns a new instance with the specified by class filtered.
     *
     * @param string $name
     * @return static
     */
    public function byClass(string $name): static
    {        
        return $this->filter(
            fn(FilterInterface $f): bool => $f instanceof $name
        );
    }
    
    /**
     * Returns a filter by name.
     *
     * @return null|object
     */
    public function get(string $name): null|object
    {
        return $this->filters[$name] ?? null;
    }
    
    /**
     * Returns the first filter or null if none.
     *
     * @return null|object
     */
    public function first(): null|object
    {
        $firstKey = array_key_first($this->all());
        
        return is_null($firstKey) ? null : $this->all()[$firstKey];
    }
    
    /**
     * Returns all filters.
     *
     * @return array<string, FilterInterface>
     */
    public function all(): array
    {
        return $this->filters;
    }
    
    /**
     * Returns the filter names.
     *
     * @return array<array-key, string>
     */
    public function names(): array
    {
        return array_keys($this->filters);
    }
    
    /**
     * Returns true if filters are empty, otherwise true.
     *
     * @return bool
     */
    public function empty(): bool
    {
        return empty($this->filters);
    }
    
    /**
     * Returns the number of filters.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->all());
    }
    
    /**
     * Get iterator.
     *
     * @return Traversable<string, FilterInterface>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }
}