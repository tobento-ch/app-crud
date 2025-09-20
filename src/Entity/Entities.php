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

use ArrayIterator;
use Generator;
use Tobento\Service\Collection\Collection;
use Tobento\Service\Iterable\Iter;
use Traversable;

/**
 * Entities
 */
final class Entities implements EntitiesInterface
{
    /**
     * @var array<int, EntityInterface>
     */
    protected array $entities = [];
    
    /**
     * @var array<int, string|int>
     */
    protected null|array $ids = null;
    
    /**
     * Create a new Entities.
     *
     * @param iterable $entities
     */
    public function __construct(
        iterable $entities = [],
    ) {
        $this->entities = Iter::toArray(iterable: $entities);
    }
    
    /**
     * Returns a new instance with the entities filtered.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static
    {
        $new = clone $this;
        $new->entities = array_filter($this->entities, $callback);
        return $new;
    }

    /**
     * Returns the first action or null if none.
     *
     * @return null|EntityInterface
     */
    public function first(): null|EntityInterface
    {
        $firstKey = array_key_first($this->all());
        
        return is_null($firstKey) ? null : $this->all()[$firstKey];
    }
    
    /**
     * Returns a new instance with the mapped entities.
     *
     * @param callable $mapper
     * @return static
     */
    public function map(callable $mapper): static
    {
        $generator = (static function(iterable $entities) use ($mapper): Generator {
            foreach($entities as $key => $entity) {
                yield $key => $mapper($entity);
            }
        })($this->entities);

        return new static($generator);
    }
    
    /**
     * Returns all entities.
     *
     * @return array<int, EntityInterface>
     */
    public function all(): array
    {
        return $this->entities;
    }
    
    /**
     * Returns all ids of the entities.
     *
     * @return array<int, string|int>
     */
    public function ids(): array
    {
        if (is_null($this->ids)) {
            $this->ids = $this->column('id');
        }
        
        return $this->ids;
    }
    
    /**
     * Returns the column of the entites.
     *
     * @param string $column
     * @param null|string $index
     * @return array
     */
    public function column(string $column, null|string $index = null): array
    {
        return array_column($this->all(), $column, $index);
    }
    
    /**
     * Returns true if has entities, otherwise false.
     *
     * @return bool
     */
    public function empty(): bool
    {
        return empty($this->entities);
    }
    
    /**
     * Returns the number of items.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->entities);
    }
    
    /**
     * Get iterator.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }
    
    /**
     * Object to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return (new Collection($this->all()))->toArray();
    }
}