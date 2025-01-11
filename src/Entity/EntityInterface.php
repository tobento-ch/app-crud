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

use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Button\ButtonsInterface;

/**
 * EntityInterface
 */
interface EntityInterface
{
    /**
     * Returns the id.
     *
     * @return int|string
     */
    public function id(): int|string;
    
    /**
     * Returns an attribute value by name and locale.
     *
     * @param string $name
     * @param mixed $default
     * @param null|string $locale
     * @return mixed
     */
    public function get(string $name, mixed $default = null, null|string $locale = null): mixed;
    
    /**
     * Returns true if entity has the given attribute, otherwise false.
     *
     * @param string|int $name
     * @return bool
     */
    public function has(string|int $name): bool;
    
    /**
     * Delete an attribute.
     *
     * @param string|int $name
     * @return static $this
     */
    public function delete(string|int $name): static;
    
    /**
     * Object to array.
     *
     * @return array
     */
    public function toArray(): array;
    
    /**
     * Sets the fields.
     *
     * @param FieldsInterface $fields
     * @return static $this
     */
    public function setFields(FieldsInterface $fields): static;

    /**
     * Returns the fields.
     *
     * @return FieldsInterface
     */
    public function fields(): FieldsInterface;
    
    /**
     * Sets the buttons.
     *
     * @param ButtonsInterface $buttons
     * @return static $this
     */
    public function setButtons(ButtonsInterface $buttons): static;
    
    /**
     * Returns the buttons.
     *
     * @return ButtonsInterface
     */
    public function buttons(): ButtonsInterface;
}