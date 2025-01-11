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

namespace Tobento\App\Crud\Input;

use Tobento\Service\Collection\Collection;

/**
 * InputInterface
 */
interface InputInterface
{
    /**
     * Has input data for the specified name.
     *
     * @param string|int $name The name.
     * @return bool
     */
    public function has(string|int $name): bool;
    
    /**
     * Returns the input data for the specified name.
     *
     * @param string|int $name The name.
     * @param mixed $default A default value. If set, the value's type should be taken into account.
     *    E.g. if string type, the returned value must be string too, otherwise the default should be returned.
     * @return mixed
     */
    public function get(string|int $name, mixed $default = null): mixed;
    
    /**
     * Set an item value by key.
     * 
     * @param string|int $name
     * @param mixed $value
     * @return static $this
     */
    public function set(string|int $name, mixed $value): static;
    
    /**
     * Delete an item from the collection.
     *
     * @param string|int $key The key.
     * @return static $this
     */
    public function delete(string|int $name): static;
    
    /**
     * Returns all input items.
     * 
     * @return array
     */
    public function all(): array;

    /**
     * Returns the input as Collection.
     * 
     * @return Collection
     */
    public function collection(): Collection;
}