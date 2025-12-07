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
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\Support\HtmlString;
use Tobento\Service\Support\Htmlable;
use Tobento\Service\Support\Str;
use Tobento\Service\Validation\Html\HtmlAttributesFactory;
use Tobento\Service\View\ViewInterface;
use function Tobento\App\Translation\trans;

/**
 * AbstractField
 */
abstract class AbstractField implements FieldInterface
{
    /**
     * @var string
     */
    protected string $name;
    
    /**
     * @var null|string
     */
    protected null|string $label = null;
    
    /**
     * @var array<string, null|callable>
     */
    protected $processors = [];
    
    /**
     * @var array<array-key, Resolve>
     */
    protected array $resolve = [];
    
    /**
     * @var string
     */
    protected string $group = 'General';
    
    /**
     * @var null|string The parent field name or null if none.
     */
    protected null|string $parent = null;
    
    /**
     * @var bool
     */
    protected bool $translatable = false;
    
    /**
     * @var string
     */
    protected string $locale = 'en';
    
    /**
     * @var array<string, string>
     */
    protected array $locales = ['en' => 'EN'];
    
    /**
     * @var bool
     */
    protected bool $storable = true;
    
    /**
     * @var bool
     */
    protected bool $indexable = true;

    /**
     * @var bool
     */
    protected bool $creatable = true;
    
    /**
     * @var bool
     */
    protected bool $editable = true;
    
    /**
     * @var bool
     */
    protected bool $showable = true;
    
    /**
     * @var bool
     */
    protected bool $tableEditable = false;
    
    /**
     * @var mixed
     */
    protected mixed $validate = null;
    
    /**
     * @var string
     */
    protected string $html = '';
    
    /**
     * @var null|EntityInterface
     */
    protected null|EntityInterface $entity = null;
    
    /**
     * @var null|EntityInterface
     */
    protected null|EntityInterface $oldEntity = null;
    
    /**
     * @var array<string, string>
     */
    protected array $requiredTexts = [];
    
    /**
     * @var array<string, string>
     */
    protected array $optionalTexts = [];
    
    /**
     * @var array<string, string|Stringable>
     */
    protected array $infoTextsAbove = [];
    
    /**
     * @var array<string, string|Stringable>
     */
    protected array $infoTextsBelow = [];
    
    /**
     * @var array
     */
    protected array $attributes = [];
    
    /**
     * @var bool
     */
    protected bool $applyValidationAttributes = true;
    
    /**
     * @var bool
     */
    protected bool $disabled = false;
    
    /**
     * @var bool
     */
    protected bool $readonly = false;
    
    /**
     * Configure field.
     *
     * @return void
     */
    protected function configure(): void
    {
        //
    }
    
    /**
     * Add or remove a processor for the specified action(s).
     *
     * @param string $action
     * @param null|callable $processor
     * @return static $this
     */
    public function process(string $action, null|callable $processor): static
    {
        foreach(explode('|', $action) as $actionName) {
            $this->processors[$actionName] = $processor;
        }

        return $this;
    }
    
    /**
     * Returns the fields processor for the specified action.
     *
     * @param string $action
     * @return null|callable
     */
    public function getProcessor(string $action): null|callable
    {
        return $this->processors[$action] ?? null;
    }
    
    /**
     * Add a resolvement.
     *
     * @param callable $resolve
     * @param null|callable $resolved
     * @param null|string $action
     * @return static $this
     */
    public function resolve(callable $resolve, null|callable $resolved = null, null|string $action = null): static
    {
        $this->resolve[] = new Resolve($resolve, $resolved, $action);
        return $this;
    }
    
    /**
     * Returns the resolvements.
     *
     * @return array<array-key, Resolve>
     */
    public function getResolve(): array
    {
        return $this->resolve;
    }
    
    /**
     * Sets the html.
     *
     * @param string|Stringable $html
     * @return static $this
     */
    public function html(string|Stringable $html): static
    {
        $this->html = (string)$html;
        return $this;
    }

    /**
     * Returns the field html.
     *
     * @return string
     */
    public function render(): string
    {
        return $this->html;
    }

    /**
     * Renames the field name.
     *
     * @param string $name
     * @return static
     */
    public function rename(string $name): static
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
    
    /**
     * Returns the label.
     *
     * @return string
     */
    public function label(): string
    {
        return $this->label ?: ucfirst($this->name);
    }

    /**
     * Set the attribute group.
     *
     * @param string $name
     * @return static $this
     */
    public function group(string $name): static
    {
        $this->group = $name;
        return $this;
    }
    
    /**
     * Returns the attribute group name.
     *
     * @return string
     */
    public function groupName(): string
    {
        return $this->group;
    }
    
    /**
     * Returns the attribute group id.
     *
     * @return string
     */
    public function groupId(): string
    {
        return strtolower(preg_replace('/[^0-9a-zA-Z-]/', '-', $this->groupName()));
    }
    
    /**
     * Set the parent field name.
     *
     * @param string $field
     * @return static $this
     */
    public function parent(string $field): static
    {
        $this->parent = $field;
        return $this;
    }
    
    /**
     * Returns the parent field name.
     *
     * @return null|string
     */
    public function parentField(): null|string
    {
        return $this->parent;
    }
    
    /**
     * Set if the attribute is translatable.
     *
     * @param bool $translatable
     * @return static $this
     */
    public function translatable(bool $translatable = true): static
    {
        $this->translatable = $translatable;
        return $this;
    }
    
    /**
     * Returns true if the attribute is translatable, otherwise false.
     *
     * @return bool
     */
    public function isTranslatable(): bool
    {
        return $this->translatable;
    }
    
    /**
     * Sets the locale.
     *
     * @param string $locale
     * @return static $this
     */
    public function setLocale(string $locale): static
    {
        $this->locale = $locale;
        return $this;
    }
    
    /**
     * Returns the locale.
     *
     * @return string
     */
    public function locale(): string
    {
        return $this->locale;
    }
    
    /**
     * Sets the locales.
     *
     * @param array<string, string> $locales
     * @return static $this
     */
    public function setLocales(array $locales): static
    {
        $this->locales = $locales;
        return $this;
    }
    
    /**
     * Returns the locales.
     *
     * @return array<string, string>
     */
    public function locales(): array
    {
        return $this->locales;
    }

    /**
     * Set if the attribute is storable.
     *
     * @param bool $storable
     * @return static $this
     */
    public function storable(bool $storable = true): static
    {
        $this->storable = $storable;
        return $this;
    }
    
    /**
     * Returns whether the attribute is storable.
     *
     * @return bool
     */
    public function isStorable(): bool
    {
        if ($this->isReadonly()) {
            return false;
        }
        
        if ($this->isDisabled()) {
            return false;
        }
        
        return $this->storable;
    }
    
    /**
     * Set if the attribute is indexable.
     *
     * @param bool $indexable
     * @return static $this
     */
    public function indexable(bool $indexable = true): static
    {
        $this->indexable = $indexable;
        return $this;
    }
    
    /**
     * Returns whether the attribute is indexable.
     *
     * @return bool
     */
    public function isIndexable(): bool
    {
        return $this->indexable;
    }

    /**
     * Set if the attribute is creatable.
     *
     * @param bool $creatable
     * @return static $this
     */
    public function creatable(bool $creatable = true): static
    {
        $this->creatable = $creatable;
        return $this;
    }
    
    /**
     * Returns whether the attribute is creatable.
     *
     * @return bool
     */
    public function isCreatable(): bool
    {
        return $this->creatable;
    }
    
    /**
     * Set if the attribute is editable.
     *
     * @param bool $editable
     * @return static $this
     */
    public function editable(bool $editable = true): static
    {
        $this->editable = $editable;
        return $this;
    }
    
    /**
     * Returns whether the attribute is editable.
     *
     * @return bool
     */
    public function isEditable(): bool
    {
        return $this->editable;
    }
    
    /**
     * Set if the field is table editable.
     *
     * @param bool $editable
     * @return static $this
     */
    public function tableEditable(bool $editable = true): static
    {
        $this->tableEditable = $editable;
        return $this;
    }
    
    /**
     * Returns whether the field is table editable.
     *
     * @return bool
     */
    public function isTableEditable(): bool
    {
        if (! $this->isEditable()) {
            return false;
        }
        
        return $this->tableEditable;
    }
    
    /**
     * Set if the attribute is showable.
     *
     * @param bool $showable
     * @return static $this
     */
    public function showable(bool $showable = true): static
    {
        $this->showable = $showable;
        return $this;
    }
    
    /**
     * Returns whether the attribute is showable.
     *
     * @return bool
     */
    public function isShowable(): bool
    {
        return $this->showable;
    }
    
    /**
     * Sets the input attributes.
     *
     * @param array $attributes
     * @return static $this
     */
    public function attributes(array $attributes): static
    {
        $this->attributes = $attributes;
        return $this;
    }
    
    /**
     * Returns the attributes.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes = $this->attributes;
        
        if ($this->isReadonly()) {
            $attributes[] = 'readonly';
        }
        
        if ($this->isDisabled()) {
            $attributes[] = 'disabled';
        }
        
        return $attributes;
    }
    
    /**
     * Sets whether applying validation attributes based from the validate rules.
     *
     * @param bool $apply
     * @return static $this
     */
    public function applyValidationAttributes(bool $apply = true): static
    {
        $this->applyValidationAttributes = $apply;
        return $this;
    }
    
    /**
     * Sets whether the field is readonly.
     *
     * @param bool|callable $readonly
     * @return static $this
     */
    public function readonly(bool|callable $readonly = true, null|string $action = null): static
    {
        if (is_null($action) && is_bool($readonly)) {
            $this->readonly = $readonly;
            return $this;
        }
        
        if (is_bool($readonly)) {
            $this->resolve(static function (FieldInterface $field) use ($readonly) {
                $field->readonly($readonly, null);
            }, action: $action);
            
            return $this;
        }
        
        $this->resolve(
            resolve: $readonly,
            resolved: static function(FieldInterface $field, mixed $resolved): void {
                if (is_bool($resolved)) {
                    $field->readonly($resolved);
                }
            },
        );
        
        return $this;
    }
    
    /**
     * Returns whether the attribute is readonly.
     *
     * @return bool
     */
    public function isReadonly(): bool
    {
        return $this->readonly;
    }
    
    /**
     * Sets whether the field is disabled.
     *
     * @param bool|callable $disabled
     * @return static $this
     */
    public function disabled(bool|callable $disabled = true, null|string $action = null): static
    {
        if (is_null($action) && is_bool($disabled)) {
            $this->disabled = $disabled;
            return $this;
        }
        
        if (is_bool($disabled)) {
            $this->resolve(static function (FieldInterface $field) use ($disabled) {
                $field->disabled($disabled, null);
            }, action: $action);
            
            return $this;
        }
        
        $this->resolve(
            resolve: $disabled,
            resolved: static function(FieldInterface $field, mixed $resolved): void {
                if (is_bool($resolved)) {
                    $field->disabled($resolved);
                }
            },
        );
        
        return $this;
    }
    
    /**
     * Returns whether the attribute is disabled.
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }
    
    /**
     * Sets the entity.
     *
     * @param EntityInterface $entity
     * @return static $this
     */
    public function setEntity(EntityInterface $entity): static
    {
        $this->entity = $entity;
        return $this;
    }
    
    /**
     * Returns the entity.
     *
     * @return EntityInterface
     */
    public function entity(): EntityInterface
    {
        if (is_null($this->entity)) {
            return new Entity();
        }
        
        return $this->entity;
    }
    
    /**
     * Sets the old entity.
     *
     * @param EntityInterface $entity
     * @return static $this
     */
    public function setOldEntity(EntityInterface $entity): static
    {
        $this->oldEntity = $entity;
        return $this;
    }
    
    /**
     * Returns the old entity.
     *
     * @return EntityInterface
     */
    public function oldEntity(): EntityInterface
    {
        if (is_null($this->oldEntity)) {
            return new Entity();
        }
        
        return $this->oldEntity;
    }
    
    /**
     * Set the attribute validate parameters.
     *
     * @param mixed ...$parameters
     * @return static $this
     */
    public function validate(mixed ...$parameters): static
    {
        $this->validate = $parameters;
        return $this;
    }
    
    /**
     * Returns the attribute validate parameters.
     *
     * @return mixed
     */
    public function getValidate(): mixed
    {
        return $this->validate;
    }

    /**
     * Returns the html validation attributes for the actionand input type.
     *
     * @param string $action
     * @param string $inputType
     * @param string $inputName
     * @return array E.g. ['min' => '5']
     */
    public function getHtmlValidationAttributes(
        string $action,
        string $inputType,
        string $inputName,
    ): array {
        if (!$this->applyValidationAttributes) {
            return [];
        }
        
        $actions = ['create' => 'store', 'edit' => 'update'];
        $action = $actions[$action] ?? $action;
        
        $rules = match (true) {
            isset($this->getValidate()[$action]) => $this->getValidate()[$action],
            isset($this->getValidate()['default']) => $this->getValidate()['default'],
            isset($this->getValidate()[0]) => $this->getValidate()[0],
            default => null,
        };
        
        if (is_null($rules)) {
            return [];
        }
        
        return (new HtmlAttributesFactory())->createAttributes(
            rules: $rules,
            inputType: $inputType,
            inputName: $inputName,
        );
    }
    
    /**
     * Returns the validation rules for the given action or null if none.
     *
     * @param string $action
     * @return null|array Must include fieldname e.g. ['fieldname' => $rules]
     */
    public function getValidationRulesForAction(string $action): null|array
    {
        $actions = ['create' => 'store', 'edit' => 'update'];
        $action = $actions[$action] ?? $action;
        
        $rules = match (true) {
            isset($this->getValidate()[$action]) => $this->getValidate()[$action],
            isset($this->getValidate()['default']) => $this->getValidate()['default'],
            isset($this->getValidate()[0]) => $this->getValidate()[0],
            default => null,
        };
        
        if (is_null($rules)) {
            return null;
        }
        
        if (!$this->isTranslatable()) {
            return [$this->name() => $rules];
        }
        
        $localizedRules = [];
        $localizedRules[$this->name()] = 'array';
        
        foreach(array_keys($this->locales()) as $locale) {
            $localizedRules[$this->name().'.'.$locale] = $rules;
        }
        
        return $localizedRules;
    }

    /**
     * Set the required text for the given action.
     *
     * @param string $text
     * @param string $action
     * @return static $this
     */
    public function requiredText(string $text, string $action = 'create|edit'): static
    {
        foreach(explode('|', $action) as $actionName) {
            $this->requiredTexts[$actionName] = $text;
        }
        
        return $this;
    }
    
    /**
     * Returns the required text.
     *
     * @param string $action
     * @return string
     */
    public function getRequiredText(string $action): string
    {
        if (isset($this->requiredTexts[$action])) {
            return $this->requiredTexts[$action];
        }
        
        $rules = $this->getValidationRulesForAction($action);
        
        if ($this->hasRequiredValidationRule(rules: $rules)) {
            return function_exists('\Tobento\App\Translation\trans') ? trans('required') : 'required';
        }
        
        return '';
    }
    
    /**
     * Set the optional text for the given action.
     *
     * @param string $text
     * @param string $action
     * @return static $this
     */
    public function optionalText(string $text, string $action = 'create|edit'): static
    {
        foreach(explode('|', $action) as $actionName) {
            $this->optionalTexts[$actionName] = $text;
        }
        
        return $this;
    }
    
    /**
     * Returns the optional text.
     *
     * @param string $action
     * @return string
     */
    public function getOptionalText(string $action): string
    {
        if (isset($this->optionalTexts[$action])) {
            return $this->optionalTexts[$action];
        }
        
        $rules = $this->getValidationRulesForAction($action);
        
        if (! $this->hasRequiredValidationRule(rules: $rules)) {
            return function_exists('\Tobento\App\Translation\trans') ? trans('optional') : 'optional';
        }
        
        return '';
    }
    
    /**
     * Set the info text for the given action.
     *
     * @param string|Stringable $text
     * @param string $action
     * @param bool $below
     * @return static $this
     */
    public function infoText(string|Stringable $text, string $action = 'create|edit', bool $below = true): static
    {
        if ($below) {
            foreach(explode('|', $action) as $actionName) {
                $this->infoTextsBelow[$actionName] = $text;
            }
            
            return $this;
        }
        
        foreach(explode('|', $action) as $actionName) {
            $this->infoTextsAbove[$actionName] = $text;
        }
        
        return $this;
    }
    
    /**
     * Returns the info text.
     *
     * @param string $action
     * @param bool $below
     * @return string|Stringable
     */
    public function getInfoText(string $action, bool $below = true): string|Stringable
    {
        $infoTexts = $below ? $this->infoTextsBelow : $this->infoTextsAbove;
        
        $text = $infoTexts[$action] ?? '';
        
        if ($text instanceof Htmlable) {
            return $text;
        }
        
        $text = (string)$text;
        
        if ($text === '') {
            return '';
        }
        
        $margin = $below ? 'mt-xs' : 'mb-xs';
        
        return new HtmlString('<p class="text-xxs '.$margin.'">'.Str::esc($text).'</p>');
    }
    
    /**
     * Processes the index action.
     *
     * @param FieldInterface $field
     * @return void
     */
    public function processIndex(FieldInterface $field): void
    {
        $html = (string)$field->entity()->get($field->name(), '', $field->locale());
        
        $field->html(Str::esc($html));
    }

    /**
     * Processes the store action.
     *
     * @param FieldInterface $field
     * @param InputInterface $input
     * @return void
     */
    public function processStore(
        FieldInterface $field,
        InputInterface $input,
    ): void {
        if (! $input->has($field->name())) {
            return;
        }
        
        if ($field->isTranslatable()) {
            $value = $input->get($field->name());
            
            if (is_string($value)) {
                $value = [$field->locale() => $value];
            }
            
            if (!is_array($value)) {
                $input->delete($field->name());
                return;
            }
            
            $data = $field->entity()->get($field->name(), []);

            foreach($value as $locale => $val) {
                if (!array_key_exists($locale, $field->locales())) {
                    continue;
                }
                $data[$locale] = $val;
            }
            
            $input->set($field->name(), $data);
        }
    }
    
    /**
     * Processes the update action.
     *
     * @param FieldInterface $field
     * @param InputInterface $input
     * @return void
     */
    public function processUpdate(
        FieldInterface $field,
        InputInterface $input,
    ): void {
        $this->processStore($field, $input);
    }
    
    /**
     * Processes the show action.
     *
     * @param FieldInterface $field
     * @param ViewInterface $view
     * @return void
     */
    public function processShow(FieldInterface $field, ViewInterface $view): void
    {
        $field->html($view->render(
            view: 'crud/field/show/field',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'renderLabel' => true,
                'text' => '',
            ],
        ));
    }
    
    /**
     * Returns true if has required validation rule, otherwise false.
     *
     * @param mixed $rules
     * @return bool
     */
    protected function hasRequiredValidationRule(mixed $rules): bool
    {
        if (!is_array($rules)) {
            return false;
        }
        
        $fieldName = $this->name();
        
        if ($this->isTranslatable()) {
            $fieldName = $this->name().'.'.$this->getFirstLocale();
        }
        
        if (!isset($rules[$fieldName])) {
            return false;
        }
        
        $rules = $rules[$fieldName];
        
        if (is_string($rules)) {
            return str_contains($rules, 'required');
        }
        
        if (is_array($rules)) {
            foreach($rules as $rule) {
                if (is_string($rule)) {
                    return str_contains($rule, 'required');
                }
            }
        }
        
        return false;
    }
    
    /**
     * Returns first locale.
     *
     * @return string
     */
    protected function getFirstLocale(): string
    {
        $locales = array_flip($this->locales());
        $firstKey = array_key_first($locales);
        return is_null($firstKey) ? 'en' : $locales[$firstKey];
    }
    
    /**
     * __get For array_column object support
     */
    public function __get(string $prop): mixed
    {
        return $this->{$prop}();
    }

    /**
     * __isset For array_column object support
     */
    public function __isset(string $prop): bool
    {
        return method_exists($this, $prop);
    }
}