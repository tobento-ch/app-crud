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

namespace Tobento\App\Crud\Field;

/**
 * Resolve
 */
class Resolve
{
    /**
     * Create a new Resolve.
     *
     * @param callable $callable
     * @param null|callable $resolved
     * @param null|string $action = null
     */
    public function __construct(
        protected $callable,
        protected $resolved = null,
        protected null|string $action = null,
    ) {}
    
    /**
     * Returns the callable.
     *
     * @return callable
     */
    public function callable(): callable
    {
        return $this->callable;
    }
    
    /**
     * Sets the resolved value from the callable.
     *
     * @param FieldInterface $field
     * @param mixed $value
     * @return void
     */
    public function resolved(FieldInterface $field, mixed $value): void
    {
        if (is_callable($this->resolved)) {
            $resolved = $this->resolved;
            $resolved($field, $value);
        }
    }
    
    /**
     * Returns true if action is supported, otherwise false.
     *
     * @param string $name
     * @return bool
     */
    public function supportsAction(string $name): bool
    {
        if (is_null($this->action)) {
            return true;
        }

        return in_array($name, explode('|', $this->action));
    }
}