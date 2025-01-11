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
     */
    public function __construct(
        protected $callable,
        protected $resolved,
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
     * @param mixed $value
     * @return void
     */
    public function resolved(mixed $value): void
    {
        if (is_callable($this->resolved)) {
            $resolved = $this->resolved;
            $resolved($value);
        }
    }
}