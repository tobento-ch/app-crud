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

namespace Tobento\App\Crud\Action;

use Traversable;
use ArrayIterator;

/**
 * Actions
 */
class Actions implements ActionsInterface
{
    /**
     * @var array<int, ActionInterface>
     */
    protected array $actions = [];
    
    /**
     * Create a new Actions.
     *
     * @param ActionInterface $action
     */
    public function __construct(
        ActionInterface ...$action,
    ) {
        $this->actions = $action;
    }
    
    /**
     * Returns a new instance with the actions filtered.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static
    {
        $new = clone $this;
        $new->actions = array_filter($this->actions, $callback);
        return $new;
    }
    
    /**
     * Returns a new instance only with the specified bulk actions.
     *
     * @return static
     */
    public function bulks(): static
    {
        return $this->filter(
            fn(ActionInterface $a): bool => $a instanceof BulkActionInterface
        );
    }

    /**
     * Returns the first action or null if none.
     *
     * @return null|ActionInterface
     */
    public function first(): null|ActionInterface
    {
        $firstKey = array_key_first($this->all());
        
        return is_null($firstKey) ? null : $this->all()[$firstKey];
    }
    
    /**
     * Returns an action by name.
     *
     * @param string $name
     * @return null|ActionInterface
     */
    public function get(string $name): null|ActionInterface
    {
        return $this->filter(
            fn(ActionInterface $a): bool => $a->name() === $name
        )->first();
    }
    
    /**
     * Returns all actions.
     *
     * @return array<int, ActionInterface>
     */
    public function all(): array
    {
        return $this->actions;
    }
    
    /**
     * Returns true if has actions, otherwise false.
     *
     * @return bool
     */
    public function empty(): bool
    {
        return empty($this->actions);
    }
    
    /**
     * Get iterator.
     *
     * @return Traversable<int, ActionInterface>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }
    
    /**
     * Returns the number of actions.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->all());
    }
}