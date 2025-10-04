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

use IteratorAggregate;
use Countable;

/**
 * @extends IteratorAggregate<int, ActionInterface>
 */
interface ActionsInterface extends IteratorAggregate, Countable
{
    /**
     * Returns a new instance with the actions filtered.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static;
    
    /**
     * Returns a new instance only with the specified bulk actions.
     *
     * @return static
     */
    public function bulks(): static;

    /**
     * Returns the first action or null if none.
     *
     * @return null|ActionInterface
     */
    public function first(): null|ActionInterface;
    
    /**
     * Returns an action by name.
     *
     * @param string $name
     * @return null|ActionInterface
     */
    public function get(string $name): null|ActionInterface;
    
    /**
     * Returns all actions.
     *
     * @return array<int, ActionInterface>
     */
    public function all(): array;
    
    /**
     * Returns true if has actions, otherwise false.
     *
     * @return bool
     */
    public function empty(): bool;
}