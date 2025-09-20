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

namespace Tobento\App\Crud\Entity;

use Countable;
use IteratorAggregate;
use Tobento\Service\Support\Arrayable;

/**
 * EntitiesInterface
 */
interface EntitiesInterface extends Countable, IteratorAggregate, Arrayable
{
    /**
     * Returns a new instance with the entities filtered.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static;

    /**
     * Returns the first action or null if none.
     *
     * @return null|EntityInterface
     */
    public function first(): null|EntityInterface;
    
    /**
     * Returns a new instance with the mapped entities.
     *
     * @param callable $mapper
     * @return static
     */
    public function map(callable $mapper): static;
    
    /**
     * Returns all entities.
     *
     * @return array<int, EntityInterface>
     */
    public function all(): array;
    
    /**
     * Returns all ids of the entities.
     *
     * @return array<int, string|int>
     */
    public function ids(): array;
    
    /**
     * Returns the column of the entites.
     *
     * @param string $column
     * @param null|string $index
     * @return array
     */
    public function column(string $column, null|string $index = null): array;
    
    /**
     * Returns true if has entities, otherwise false.
     *
     * @return bool
     */
    public function empty(): bool;
}