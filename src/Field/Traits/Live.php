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

trait Live
{
    /**
     * @var array<string, mixed>
     */
    protected array $live = [];

    /**
     * Formats value.
     *
     * @param array $fields
     * @param array $selectors
     * @param bool $onBlur
     * @param int $debounce
     * @param null|callable $after
     * @param string $action
     * @return static $this
     */
    public function live(
        array $fields = [],
        array $selectors = [],
        bool $onBlur = false,
        int $debounce = 0,
        null|callable $after = null,
        string $action = 'create|edit|copy',
    ): static {
        foreach(explode('|', $action) as $actionName) {
            $this->live[$actionName] = [
                'fields' => $fields,
                'selectors' => $selectors,
                'blur' => $onBlur,
                'debounce' => $debounce,
                'after' => $after,
            ];
        }

        return $this;
    }
    
    /**
     * Returns the after live handler.
     *
     * @param string $action
     * @return null|callable
     */
    public function getAfterLiveHandler(string $action): null|callable
    {
        return $this->live[$action]['after'] ?? null;
    }
    
    /**
     * Returns the live parameters for the given action.
     *
     * @param string $action
     * @return array
     */
    public function getLiveParameters(string $action): array
    {
        return $this->live[$action] ?? [];
    }

    /**
     * Returns whether live parameters for the given action exists or not.
     *
     * @param string $action
     * @return bool
     */
    public function hasLiveParameters(string $action): bool
    {
        return isset($this->live[$action]);
    }

    /**
     * Assigns live attributes for the given action.
     *
     * @param string $action
     * @param array $attributes
     * @return array
     */
    public function assignLiveAttributes(string $action, array $attributes): array
    {
        if ($this->hasLiveParameters($action)) {
            $attrs = $this->getLiveParameters($action);
            unset($attrs['after']);
            $attributes['data-live'] = $attrs;
        }
        
        return $attributes;
    }
}