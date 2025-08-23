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

namespace Tobento\App\Crud\Field\Traits;

use Tobento\App\Crud\Field\FieldInterface;

trait HasValueFormatter
{
    /**
     * @var array<string, callable>
     */
    protected array $valueFormatters = [];

    /**
     * Formats value.
     *
     * @param callable $formatter
     * @param string $action
     * @return static $this
     */
    public function formatValue(callable $formatter, string $action = 'index|show'): static
    {
        foreach(explode('|', $action) as $actionName) {
            $this->valueFormatters[$actionName] = $formatter;
        }

        return $this;
    }

    /**
     * Returns whether a value formatter for the given action exists or not.
     *
     * @param string $action
     * @return bool
     */
    protected function hasValueFormatter(string $action): bool
    {
        return isset($this->valueFormatters[$action]);
    }
    
    /**
     * Formatting value.
     *
     * @param string $action
     * @param mixed $value
     * @param FieldInterface $field
     * @return mixed
     */
    protected function formattingValue(string $action, mixed $value, FieldInterface $field): mixed
    {
        if (!isset($this->valueFormatters[$action])) {
            return $value;
        }
        
        return ($this->valueFormatters[$action])($value, $field);
    }
}