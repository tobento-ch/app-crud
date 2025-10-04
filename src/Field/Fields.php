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

use Traversable;
use ArrayIterator;
use Tobento\App\Crud\Action\ActionInterface;

/**
 * Fields
 */
class Fields implements FieldsInterface
{
    /**
     * @var array<string, FieldInterface>
     */
    protected array $fields = [];
    
    /**
     * Create a new Fields.
     *
     * @param FieldInterface $fields
     */
    public function __construct(
        FieldInterface ...$fields,
    ) {
        $this->addFields($fields);
    }

    /**
     * Adds the fields.
     *
     * @param array<array-key, FieldInterface>|FieldsInterface $fields
     * @return void
     */
    protected function addFields(array|FieldsInterface $fields): void
    {
        foreach($fields as $field) {
            $this->fields[$field->name()] = $field;
        }
    }
    
    /**
     * Returns a new instance with the fields filtered.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static
    {
        $new = clone $this;
        $new->fields = array_filter($this->fields, $callback);
        return $new;
    }

    /**
     * Returns a new instance with the specified group.
     *
     * @param string $name
     * @return static
     */
    public function group(string $name): static
    {
        return $this->filter(
            fn(FieldInterface $a): bool => $a->groupName() === $name
        );
    }
    
    /**
     * Returns a new instance with the specified parent.
     *
     * @param string $field
     * @return static
     */
    public function parent(null|string $field): static
    {
        return $this->filter(
            fn(FieldInterface $a): bool => $a->parentField() === $field
        );
    }
    
    /**
     * Returns a new instance with the included parents fields.
     *
     * @param ActionInterface $action
     * @return static
     */
    public function withParentFields(ActionInterface $action): static
    {
        $fields = [];
        
        foreach($this->all() as $field) {
            $fields[$field->name()] = $field;
            
            if ($field instanceof ParentFieldsAwareInterface) {
                foreach($field->getFields($action) as $f) {
                    $fields[$f->name()] = $f;
                }
            }
        }
        
        $new = clone $this;
        $new->fields = $fields;
        return $new;
    }
    
    /**
     * Returns a new instance with the included child fields.
     *
     * @param ActionInterface $action
     * @return static
     */
    public function withChildFields(ActionInterface $action): static
    {
        $new = clone $this;
        $new->fields = $this->collectChildFields($new, $action);
        return $new;
    }
    
    /**
     * Returns a new instance with (un)translatable fields only.
     *
     * @param bool $translatable
     * @return static
     */
    public function translatable(bool $translatable = true): static
    {
        return $this->filter(
            fn(FieldInterface $a): bool => $a->isTranslatable() === $translatable
        );
    }

    /**
     * Returns a new instance with (un)creatable fields only.
     *
     * @param bool $creatable
     * @return static
     */
    public function creatable(bool $creatable = true): static
    {
        return $this->filter(
            fn(FieldInterface $a): bool => $a->isCreatable() === $creatable
        );
    }
    
    /**
     * Returns a new instance with (un)editable fields only.
     *
     * @param bool $editable
     * @return static
     */
    public function editable(bool $editable = true): static
    {
        return $this->filter(
            fn(FieldInterface $a): bool => $a->isEditable() === $editable
        );
    }
    
    /**
     * Returns a new instance with (un)showable fields only.
     *
     * @param bool $showable
     * @return static
     */
    public function showable(bool $showable = true): static
    {
        return $this->filter(
            fn(FieldInterface $a): bool => $a->isShowable() === $showable
        );
    }
    
    /**
     * Returns a new instance with (un)storable fields only.
     *
     * @param bool $storable
     * @return static
     */
    public function storable(bool $storable = true): static
    {
        return $this->filter(
            fn(FieldInterface $a): bool => $a->isStorable() === $storable
        );
    }
    
    /**
     * Gets field column.
     *
     * @param string $key
     * @param string $index
     * @return array
     */
    public function column(string $key, null|string $index = null): array
    {
        return array_column($this->all(), $key, $index);
    }

    /**
     * Returns the field names.
     *
     * @return array<int, string>
     */
    public function getNames(): array
    {
        return $this->column('name');
    }
    
    /**
     * Returns an field by name.
     *
     * @return null|FieldInterface
     */
    public function get(string $name): null|FieldInterface
    {
        return $this->fields[$name] ?? null;
    }
    
    /**
     * Returns all fields.
     *
     * @return array<string, FieldInterface>
     */
    public function all(): array
    {
        return $this->fields;
    }
    
    /**
     * Returns true if fields are empty, otherwise true.
     *
     * @return bool
     */
    public function empty(): bool
    {
        return empty($this->fields);
    }
    
    /**
     * Returns the first found primary field or null if none.
     *
     * @param string $name The action name
     * @return array<string, mixed>
     */
    public function getValidationRulesForAction(string $name): array
    {
        $rules = [];
        
        foreach($this->all() as $field) {
            if (!is_null($fieldRules = $field->getValidationRulesForAction(action: $name))) {
                $rules = array_merge($rules, $fieldRules);
            }
        }
        
        return $rules;
    }
    
    /**
     * Get iterator.
     *
     * @return Traversable<string, FieldInterface>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }
    
    /**
     * Returns the number of fields.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->all());
    }
    
    /**
     * Clone.
     */
    public function __clone()
    {
        foreach($this->all() as $field) {
            $this->fields[$field->name()] = clone $field;
        }
    }
    
    /**
     * Collects child fields.
     *
     * @param FieldsInterface $fields
     * @param ActionInterface $action
     * @param array<array-key, FieldInterface> $items The previous collected fields
     * @return array<array-key, FieldInterface>
     */
    protected function collectChildFields(FieldsInterface $fields, ActionInterface $action, $items = []): array
    {
        foreach($fields as $field) {
            if ($field instanceof FieldsAwareInterface) {
                $items = $this->collectChildFields($field->getFields($action), $action, $items);
            }
            $items[] = $field;
        }
        
        return $items;
    }
}