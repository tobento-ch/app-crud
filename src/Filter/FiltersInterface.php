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

use IteratorAggregate;
use Countable;

/**
 * @extends IteratorAggregate<string, FilterInterface>
 */
interface FiltersInterface extends IteratorAggregate, Countable
{
    /**
     * Returns the applied parameters.
     *
     * @return array
     */
    public function getAppliedParameters(): array;
    
    /**
     * Returns the where parameters.
     *
     * @return array
     */
    public function getWhereParameters(): array;
    
    /**
     * Returns the order by parameters.
     *
     * @return array
     */
    public function getOrderByParameters(): array;
    
    /**
     * Returns the limit parameter.
     *
     * @return array
     */
    public function getLimitParameter(): array;
    
    /**
     * Returns a new instance with the filters filtered.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static;

    /**
     * Returns a new instance with the specified group filtered.
     *
     * @param string $name
     * @return static
     */
    public function group(string $name): static;
    
    /**
     * Returns a new instance with the specified field filtered.
     *
     * @param string $name
     * @return static
     */
    public function field(string $name): static;
    
    /**
     * Returns a new instance with the specified open filtered.
     *
     * @param bool $open
     * @return static
     */
    public function open(bool $open = true): static;
    
    /**
     * Returns a new instance with the specified by class filtered.
     *
     * @param string $name
     * @return static
     */
    public function byClass(string $name): static;
    
    /**
     * Returns a filter by name.
     *
     * @return null|object
     */
    public function get(string $name): null|object;
    
    /**
     * Returns the first filter or null if none.
     *
     * @return null|object
     */
    public function first(): null|object;
    
    /**
     * Returns all filters.
     *
     * @return array<string, FilterInterface>
     */
    public function all(): array;
    
    /**
     * Returns the filter names.
     *
     * @return array<array-key, string>
     */
    public function names(): array;
    
    /**
     * Returns true if filters are empty, otherwise true.
     *
     * @return bool
     */
    public function empty(): bool;
}