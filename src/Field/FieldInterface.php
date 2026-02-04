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

use Stringable;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Input\InputInterface;

/**
 * FieldInterface
 */
interface FieldInterface
{
    /**
     * Add or remove a processor for the specified action(s).
     *
     * @param string $action
     * @param null|callable $processor
     * @return static $this
     */
    public function process(string $action, null|callable $processor): static;
    
    /**
     * Returns the fields processor for the specified action.
     *
     * @return null|callable
     */
    public function getProcessor(string $action): null|callable;
    
    /**
     * Returns the resolvements.
     *
     * @return array<array-key, Resolve>
     */
    public function getResolve(): array;

    /**
     * Sets the html.
     *
     * @param string $html
     * @return static $this
     */
    public function html(string $html): static;
    
    /**
     * Returns the field html.
     *
     * @return string
     */
    public function render(): string;
    
    /**
     * Renames the field name.
     *
     * @param string $name
     * @return static
     */
    public function rename(string $name): static;
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string;
    
    /**
     * Returns the label.
     *
     * @return string
     */
    public function label(): string;
    
    /**
     * Set the attribute group.
     *
     * @param string $name
     * @return static $this
     */
    public function group(string $name): static;
    
    /**
     * Returns the attribute group name.
     *
     * @return string
     */
    public function groupName(): string;
    
    /**
     * Returns the attribute group id.
     *
     * @return string
     */
    public function groupId(): string;
    
    /**
     * Set the parent field name.
     *
     * @param string $field
     * @return static $this
     */
    public function parent(string $field): static;
    
    /**
     * Returns the parent field name.
     *
     * @return null|string
     */
    public function parentField(): null|string;
    
    /**
     * Set if the attribute is translatable.
     *
     * @param bool $translatable
     * @return static $this
     */
    public function translatable(bool $translatable = true): static;
    
    /**
     * Returns true if the attribute is a translatable, otherwise false.
     *
     * @return bool
     */
    public function isTranslatable(): bool;
    
    /**
     * Sets the locale.
     *
     * @param string $locale
     * @return static $this
     */
    public function setLocale(string $locale): static;
    
    /**
     * Returns the locale.
     *
     * @return string
     */
    public function locale(): string;
    
    /**
     * Sets the locales.
     *
     * @param array<string, string> $locales
     * @return static $this
     */
    public function setLocales(array $locales): static;
    
    /**
     * Returns the locales.
     *
     * @return array<string, string>
     */
    public function locales(): array;

    /**
     * Set if the attribute is storable.
     *
     * @param bool $storable
     * @return static $this
     */
    public function storable(bool $storable = true): static;
    
    /**
     * Returns whether the attribute is storable.
     *
     * @return bool
     */
    public function isStorable(): bool;
    
    /**
     * Set if the attribute is indexable.
     *
     * @param bool $indexable
     * @return static $this
     */
    public function indexable(bool $indexable = true): static;
    
    /**
     * Returns whether the attribute is indexable.
     *
     * @return bool
     */
    public function isIndexable(): bool;
    
    /**
     * Set if the attribute is creatable.
     *
     * @param bool $creatable
     * @return static $this
     */
    public function creatable(bool $creatable = true): static;
    
    /**
     * Returns whether the attribute is creatable.
     *
     * @return bool
     */
    public function isCreatable(): bool;
    
    /**
     * Set if the attribute is editable.
     *
     * @param bool $editable
     * @return static $this
     */
    public function editable(bool $editable = true): static;
    
    /**
     * Returns whether the attribute is editable.
     *
     * @return bool
     */
    public function isEditable(): bool;
    
    /**
     * Set if the field is table editable.
     *
     * @param bool $editable
     * @return static $this
     */
    public function tableEditable(bool $editable = true): static;
    
    /**
     * Returns whether the field is table editable.
     *
     * @return bool
     */
    public function isTableEditable(): bool;
    
    /**
     * Set if the attribute is showable.
     *
     * @param bool $showable
     * @return static $this
     */
    public function showable(bool $showable = true): static;
    
    /**
     * Returns whether the attribute is showable.
     *
     * @return bool
     */
    public function isShowable(): bool;
    
    /**
     * Sets the input attributes.
     *
     * @param array $attributes
     * @return static $this
     */
    public function attributes(array $attributes): static;
    
    /**
     * Returns the attributes.
     *
     * @return array
     */
    public function getAttributes(): array;
    
    /**
     * Returns whether the attribute is readonly.
     *
     * @return bool
     */
    public function isReadonly(): bool;
    
    /**
     * Returns whether the attribute is disabled.
     *
     * @return bool
     */
    public function isDisabled(): bool;
    
    /**
     * Sets the entity.
     *
     * @param EntityInterface $entity
     * @return static $this
     */
    public function setEntity(EntityInterface $entity): static;
    
    /**
     * Returns the entity.
     *
     * @return EntityInterface
     */
    public function entity(): EntityInterface;
    
    /**
     * Sets the old entity.
     *
     * @param EntityInterface $entity
     * @return static $this
     */
    public function setOldEntity(EntityInterface $entity): static;
    
    /**
     * Returns the old entity.
     *
     * @return EntityInterface
     */
    public function oldEntity(): EntityInterface;
    
    /**
     * Set the attribute validate parameters.
     *
     * @param mixed ...$parameters
     * @return static $this
     */
    public function validate(mixed ...$parameters): static;
    
    /**
     * Returns the attribute validate parameters.
     *
     * @return mixed
     */
    public function getValidate(): mixed;
    
    /**
     * Returns the validation rules for the given action or null if none.
     *
     * @param string $action
     * @return null|array Must include fieldname e.g. ['fieldname' => $rules]
     */
    public function getValidationRulesForAction(string $action): null|array;
    
    /**
     * Set the required text for the given action.
     *
     * @param string|Stringable $text
     * @param string $action
     * @return static $this
     */
    public function requiredText(string|Stringable $text, string $action = 'create|edit'): static;
    
    /**
     * Returns the required text.
     *
     * @param string $action
     * @return string|Stringable
     */
    public function getRequiredText(string $action): string|Stringable;
    
    /**
     * Set the optional text for the given action.
     *
     * @param string|Stringable $text
     * @param string $action
     * @return static $this
     */
    public function optionalText(string|Stringable $text, string $action = 'create|edit'): static;
    
    /**
     * Returns the optional text.
     *
     * @param string $action
     * @return string|Stringable
     */
    public function getOptionalText(string $action): string|Stringable;
    
    /**
     * Set the info text for the given action.
     *
     * @param string|Stringable $text
     * @param string $action
     * @param bool $below
     * @return static $this
     */
    public function infoText(string|Stringable $text, string $action = 'create|edit', bool $below = true): static;
    
    /**
     * Returns the info text.
     *
     * @param string $action
     * @param bool $below
     * @return string|Stringable
     */
    public function getInfoText(string $action, bool $below = true): string|Stringable;
    
    /**
     * Sets whether the field is readonly.
     *
     * @param bool|callable $readonly
     * @return static $this
     */
    public function readonly(bool|callable $readonly = true, null|string $action = null): static;
    
    /**
     * Sets whether the field is disabled.
     *
     * @param bool|callable $disabled
     * @return static $this
     */
    public function disabled(bool|callable $disabled = true, null|string $action = null): static;
}