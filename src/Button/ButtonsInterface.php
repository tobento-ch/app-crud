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

namespace Tobento\App\Crud\Button;

use IteratorAggregate;
use Countable;

/**
 * @extends IteratorAggregate<string, ButtonInterface>
 */
interface ButtonsInterface extends IteratorAggregate, Countable
{
    /**
     * Returns the button or null if none.
     *
     * @param string $name
     * @return null|ButtonInterface
     */
    public function get(string $name): null|ButtonInterface;
    
    /**
     * Adds a button or multiple.
     *
     * @param ButtonInterface ...$buttons
     * @return static $this
     */
    public function add(ButtonInterface ...$buttons): static;
    
    /**
     * Removes a button or multiple.
     *
     * @param string ...$names
     * @return static $this
     */
    public function remove(string ...$names): static;
    
    /**
     * Returns a new instance with the buttons orderd by the names set.
     *
     * @param string ...$names
     * @return static
     */
    public function reorder(string ...$names): static;
    
    /**
     * Returns a new instance with the buttons filtered.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static;
    
    /**
     * Returns a new instance only with the specified group.
     *
     * @param string $group
     * @return static
     */
    public function group(string $group): static;
    
    /**
     * Returns the first button or null if none.
     *
     * @return null|ButtonInterface
     */
    public function first(): null|ButtonInterface;
    
    /**
     * Returns all buttons.
     *
     * @return array<string, ButtonInterface>
     */
    public function all(): array;
    
    /**
     * Returns the button names.
     *
     * @return array<array-key, string>
     */
    public function names(): array;
    
    /**
     * Returns true if has buttons, otherwise false.
     *
     * @return bool
     */
    public function has(): bool;
}