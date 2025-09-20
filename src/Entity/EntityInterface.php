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
use Tobento\Service\Support\Arrayable;

interface EntityInterface extends Arrayable
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