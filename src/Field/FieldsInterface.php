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

use IteratorAggregate;
use Countable;
use Tobento\App\Crud\Action\ActionInterface;

/**
 * FieldsInterface
 */
interface FieldsInterface extends IteratorAggregate, Countable
{
    /**
     * Returns a new instance with the fields filtered.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static;

    /**
     * Returns a new instance with the specified group.
     *
     * @param string $name
     * @return static
     */
    public function group(string $name): static;
    
    /**
     * Returns a new instance with the specified parent.
     *
     * @param string $field
     * @return static
     */
    public function parent(null|string $field): static;
    
    /**
     * Returns a new instance with the included parents fields.
     *
     * @param ActionInterface $action
     * @return static
     */
    public function withParentFields(ActionInterface $action): static;
    
    /**
     * Returns a new instance with the included child fields.
     *
     * @param ActionInterface $action
     * @return static
     */
    public function withChildFields(ActionInterface $action): static;
    
    /**
     * Returns a new instance with (un)translatable fields only.
     *
     * @param bool $translatable
     * @return static
     */
    public function translatable(bool $translatable = true): static;
    
    /**
     * Returns a new instance with (un)creatable fields only.
     *
     * @param bool $creatable
     * @return static
     */
    public function creatable(bool $creatable = true): static;
    
    /**
     * Returns a new instance with (un)editable fields only.
     *
     * @param bool $editable
     * @return static
     */
    public function editable(bool $editable = true): static;
    
    /**
     * Returns a new instance with (un)showable fields only.
     *
     * @param bool $showable
     * @return static
     */
    public function showable(bool $showable = true): static;
    
    /**
     * Returns a new instance with (un)storable fields only.
     *
     * @param bool $storable
     * @return static
     */
    public function storable(bool $storable = true): static;
    
    /**
     * Gets field column.
     *
     * @param string $key
     * @param string $index
     * @return array
     */
    public function column(string $key, null|string $index = null): array;

    /**
     * Returns the field names.
     *
     * @return array<int, string>
     */
    public function getNames(): array;
    
    /**
     * Returns an field by name.
     *
     * @return null|FieldInterface
     */
    public function get(string $name): null|FieldInterface;
    
    /**
     * Returns all fields.
     *
     * @return array<string, FieldInterface>
     */
    public function all(): array;
    
    /**
     * Returns true if fields are empty, otherwise true.
     *
     * @return bool
     */
    public function empty(): bool;
    
    /**
     * Returns the first found primary field or null if none.
     *
     * @param string $name The action name
     * @return array<string, mixed>
     */
    public function getValidationRulesForAction(string $name): array;
}