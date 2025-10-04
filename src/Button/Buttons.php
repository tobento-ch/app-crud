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

use Traversable;
use ArrayIterator;

/**
 * Buttons
 */
class Buttons implements ButtonsInterface
{
    /**
     * @var array<string, ButtonInterface>
     */
    protected array $buttons = [];
    
    /**
     * Create a new Buttons.
     *
     * @param ButtonInterface $button
     */
    public function __construct(
        ButtonInterface ...$button,
    ) {
        $this->add(...$button);
    }

    /**
     * Returns the button or null if none.
     *
     * @param string $name
     * @return null|ButtonInterface
     */
    public function get(string $name): null|ButtonInterface
    {
        return $this->buttons[$name] ?? null;
    }
    
    /**
     * Adds a button or multiple.
     *
     * @param ButtonInterface ...$buttons
     * @return static $this
     */
    public function add(ButtonInterface ...$buttons): static
    {
        foreach($buttons as $button) {
            $this->buttons[$button->getName()] = $button;
        }
        
        return $this;
    }
    
    /**
     * Removes a button or multiple.
     *
     * @param string ...$names
     * @return static $this
     */
    public function remove(string ...$names): static
    {
        $this->buttons = array_filter(
            $this->buttons,
            fn(ButtonInterface $b): bool => !in_array($b->getName(), $names)
        );
        
        return $this;
    }
    
    /**
     * Returns a new instance with the buttons orderd by the names set.
     *
     * @param string ...$names
     * @return static
     */
    public function reorder(string ...$names): static
    {
        $orderedButtons = [];
        $buttons = $this->all();
        
        foreach($names as $name) {
            if (isset($buttons[$name])) {
                $orderedButtons[$name] = $buttons[$name];
                unset($buttons[$name]);
            }
        }
        
        foreach($buttons as $name => $button) {
            $orderedButtons[$name] = $button;
        }
        
        $new = clone $this;
        $new->buttons = $orderedButtons;
        return $new;
    }
    
    /**
     * Returns a new instance with the buttons filtered.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static
    {
        $new = clone $this;
        $new->buttons = array_filter($this->buttons, $callback);
        return $new;
    }
    
    /**
     * Returns a new instance only with the specified group.
     *
     * @param string $group
     * @return static
     */
    public function group(string $group): static
    {
        return $this->filter(
            fn(ButtonInterface $b): bool => $b->getGroup() === $group
        );
    }
    
    /**
     * Returns the first button or null if none.
     *
     * @return null|ButtonInterface
     */
    public function first(): null|ButtonInterface
    {
        $firstKey = array_key_first($this->all());
        
        return is_null($firstKey) ? null : $this->all()[$firstKey];
    }
    
    /**
     * Returns all buttons.
     *
     * @return array<string, ButtonInterface>
     */
    public function all(): array
    {
        return $this->buttons;
    }
    
    /**
     * Returns the button names.
     *
     * @return array<array-key, string>
     */
    public function names(): array
    {
        return array_keys($this->buttons);
    }
    
    /**
     * Returns true if has buttons, otherwise false.
     *
     * @return bool
     */
    public function has(): bool
    {
        return !empty($this->buttons);
    }
    
    /**
     * Get iterator.
     *
     * @return Traversable<string, ButtonInterface>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }
    
    /**
     * Returns the number of buttons.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->all());
    }
}