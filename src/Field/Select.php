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

use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\View\ViewInterface;
use Tobento\Service\Iterable\Iter;
use Tobento\Service\Collection\Collection;
use Tobento\Service\Support\Str;
use Tobento\Service\Validation\ValidatorInterface;
use Tobento\Service\Validation\Rule\Passes;
use InvalidArgumentException;

/**
 * Select
 */
class Select extends AbstractField
{
    /**
     * @var iterable
     */
    protected iterable $options = [];
    
    /**
     * @var null|array
     */
    protected null|array $emptyOption = null;
    
    /**
     * @var string|iterable
     */
    protected null|string|iterable $selected = null;
    
    /**
     * @var array
     */
    protected array $optionAttributes = [];
    
    /**
     * @var array
     */
    protected array $optgroupAttributes = [];
    
    /**
     * Create a new Select.
     *
     * @param string $name
     * @param null|string $label
     */
    final public function __construct(
        string $name,
        null|string $label = null,
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->process('index', [$this, 'processIndexAction']);
        $this->process('show', [$this, 'processShow']);
        $this->process('create|edit|copy', [$this, 'processCreateEdit']);
        $this->process('store|update', [$this, 'processSave']);
        
        // call validate as to add validOptionsRule:
        $this->validate([]);
        
        $this->configure();
    }
    
    /**
     * Create a new instance.
     *
     * @param string $name
     * @param null|string $label
     * @return static
     */
    public static function new(string $name, null|string $label = null): static
    {
        return new static($name, $label);
    }
    
    /**
     * Sets the options.
     *
     * @param iterable|callable $options
     * @return static $this
     */
    public function options(iterable|callable $options): static
    {
        if (is_iterable($options)) {
            $this->options = $options;
            return $this;
        }
        
        $this->resolve(
            resolve: $options,
            resolved: function(Select $field, mixed $resolved): void {
                if (is_iterable($resolved)) {
                    $field->options($resolved);
                    $this->options($resolved); // used for validation rule.
                }
            },
        );
        
        return $this;
    }
    
    /**
     * Returns the options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return Iter::toArray(iterable: $this->options);
    }
    
    /**
     * Sets the empty option.
     *
     * @param string $value
     * @param string $label
     * @return static $this
     */
    public function emptyOption(string $value, string $label): static
    {
        $this->emptyOption = [$value, $label];
        return $this;
    }
    
    /**
     * Returns the empty option.
     *
     * @return null|array
     */
    public function getEmptyOption(): null|array
    {
        return $this->emptyOption;
    }
    
    /**
     * Sets the selected for the given action(s).
     *
     * @param string|iterable|callable $value
     * @param null|string $action
     * @return static $this
     */
    public function selected(string|iterable|callable $value, null|string $action = 'create'): static
    {
        if (is_null($action) && is_string($value) || is_iterable($value)) {
            $this->selected = $value;
            return $this;
        }
        
        if (is_string($value) || is_iterable($value)) {
            $this->resolve(function (Select $field) use ($value) {
                $field->selected($value, null);
            }, action: $action);
            
            return $this;
        }
        
        $this->resolve(
            resolve: $value,
            resolved: function(Select $field, mixed $resolved): void {
                if (is_string($resolved) || is_iterable($resolved)) {
                    $field->selected($resolved, null);
                }
            },
            action: $action,
        );
        
        return $this;
    }
    
    /**
     * Returns the selected.
     *
     * @return null|string|array
     */
    public function getSelected(): null|string|array
    {
        if (is_iterable($this->selected)) {
            return Iter::toArray(iterable: $this->selected);
        }
        
        return $this->selected;
    }
    
    /**
     * Returns true if multiple selection, otherwise false.
     *
     * @return bool
     */
    public function isMultipleSelection(): bool
    {
        if (in_array('multiple', $this->getAttributes())) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Sets the select option attributes.
     *
     * @param array $attributes
     * @return static $this
     */
    public function optionAttributes(array $attributes): static
    {
        $this->optionAttributes = $attributes;
        return $this;
    }
    
    /**
     * Returns the select option attributes.
     *
     * @return array
     */
    public function getOptionAttributes(): array
    {
        return $this->optionAttributes;
    }
    
    /**
     * Sets the select optgroup attributes.
     *
     * @param array $attributes
     * @return static $this
     */
    public function optgroupAttributes(array $attributes): static
    {
        $this->optgroupAttributes = $attributes;
        return $this;
    }
    
    /**
     * Returns the select optgroup attributes.
     *
     * @return array
     */
    public function getOptgroupAttributes(): array
    {
        return $this->optgroupAttributes;
    }
    
    /**
     * Set if the attribute is translatable.
     *
     * @param bool $translatable
     * @return static $this
     */
    public function translatable(bool $translatable = true): static
    {
        throw new InvalidArgumentException('Field does not support translations');
    }
    
    /**
     * Set the attribute validate parameters.
     *
     * @param mixed ...$parameters
     * @return static $this
     */
    public function validate(mixed ...$parameters): static
    {
        foreach($parameters as $key => $parameter) {
            $parameters[$key] = !is_array($parameter)
                ? [$parameter, $this->validOptionsRule()]
                : array_merge($parameter, [$this->validOptionsRule()]);
        }
        
        $this->validate = $parameters;
        return $this;
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
        if ($this->isMultipleSelection()) {
            $options = $field->entity()->get($field->name(), []);
            $options = implode(', ', $options);
            
            $field->html($view->render(
                view: 'crud/field/show/field',
                data: [
                    'field' => $field,
                    'entity' => $field->entity(),
                    'renderLabel' => true,
                    'text' => $options,
                ],
            ));
            
            return;
        }
        
        $option = $field->entity()->get($field->name());
        
        if (!is_scalar($option)) {
            $option = '';
        }
        
        $option = (string)$option;
        $option = $this->getOptions()[$option] ?? $option;
        
        $field->html($view->render(
            view: 'crud/field/show/field',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'renderLabel' => true,
                'text' => $option,
            ],
        ));
    }
        
    /**
     * Processes the create and edit action.
     *
     * @param ActionInterface $action
     * @param Select $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processCreateEdit(
        ActionInterface $action,
        Select $field,
        ViewInterface $view,
    ): void {
        $form = $view->form();        
        $attributes = $field->getAttributes();
        $attributes['id'] ??= $form->nameToId($field->name());
        $attributes = array_merge(
            $field->getHtmlValidationAttributes(
                $action->name(),
                'select',
                $view->trans($field->label())
            ),
            $attributes,
        );
        
        $name = $form->nameToArray($field->name());
        
        if ($this->isMultipleSelection()) {
            $name = $name.'.';
        }
        
        $body = $form->select(
            name: $name,
            items: $field->getOptions(),
            selected: $field->entity()->get($field->name(), $field->getSelected()),
            selectAttributes: $attributes,
            optionAttributes: $this->getOptionAttributes(),
            optgroupAttributes: $this->getOptgroupAttributes(),
            emptyOption: $field->getEmptyOption(),
            withInput: true,
        );
        
        $field->html($view->render(
            view: 'crud/field/select',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'body' => $body,
                'hasLabels' => false,
            ],
        ));
    }
        
    /**
     * Processes the store and update action.
     *
     * @param Select $field
     * @param InputInterface $input
     * @return void
     */
    public function processSave(
        Select $field,
        InputInterface $input,
    ): void {
        if (! $input->has($field->name())) {
            return;
        }
        
        $options = $input->get($field->name());
        
        if (! $field->isMultipleSelection()) {
            if (isset($field->getEmptyOption()[0]) && $options === $field->getEmptyOption()[0]) {
                $input->set($field->name(), '');
                return;
            }
            
            return;
        }
        
        // multiple:
        if (!is_array($options)) {
            $input->delete($field->name());
            return;
        }
        
        $options = (new Collection(array_flip($options)));
        
        if ($field->getEmptyOption()) {
            $options = $options->except([$field->getEmptyOption()[0]]);
        }

        $input->set($field->name(), array_flip($options->all()));
    }
    
    /**
     * Processes the index action.
     *
     * @param ActionInterface $action
     * @param Select $field
     * @param ViewInterface $view
     * @return void
     */
    public function processIndexAction(ActionInterface $action, Select $field, ViewInterface $view): void
    {
        if ($this->isTableEditable()) {
            $this->processIndexTable($action, $field, $view);
            return;
        }
        
        $this->processIndex($field);
    }
    
    /**
     * Processes the index action.
     *
     * @param FieldInterface $field
     * @return void
     */
    public function processIndex(FieldInterface $field): void
    {
        if ($this->isMultipleSelection()) {
            $options = $field->entity()->get($field->name(), []);
            $options = implode(', ', $options);
            $options = mb_strimwidth($options, 0, 100, '...');
            $field->html(Str::esc($options));
            return;
        }
        
        $option = $field->entity()->get($field->name());
        
        if (! is_scalar($option)) {
            $option = '';
        }
        
        $option = (string)$option;
        $option = $this->getOptions()[$option] ?? $option;
        
        $field->html(Str::esc($option));
    }
    
    /**
     * Processes the index table action.
     *
     * @param ActionInterface $action
     * @param Select $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    protected function processIndexTable(ActionInterface $action, Select $field, ViewInterface $view): void
    {
        $form = $view->form();        
        $attributes = $field->getAttributes();
        $attributes['id'] = '';
        $attributes['tabindex'] = '5';
        $attributes = array_merge(
            $field->getHtmlValidationAttributes(
                $action->name(),
                'select',
                $view->trans($field->label())
            ),
            $attributes,
        );
        
        $name = $form->nameToArray($field->name());
        
        if ($this->isMultipleSelection()) {
            $name = $name.'.';
        }
        
        $html = $form->select(
            name: $name,
            items: $field->getOptions(),
            selected: $field->entity()->get($field->name(), $field->getSelected()),
            selectAttributes: $attributes,
            optionAttributes: $this->getOptionAttributes(),
            optgroupAttributes: $this->getOptgroupAttributes(),
            emptyOption: $field->getEmptyOption(),
            withInput: true,
        );
        
        $field->html($html);
    }
    
    /**
     * Returns the valid options rule.
     *
     * @return Passes
     */
    protected function validOptionsRule(): Passes
    {
        return new Passes(
            passes: function(mixed $value): bool {
                $options = $this->getOptions();
                
                if ($this->emptyOption) {
                    $options[$this->emptyOption[0]] = $this->emptyOption[1];
                }

                if (
                    is_string($value)
                    && ! $this->isMultipleSelection()
                    && array_key_exists($value, $options)
                ) {
                    return true;
                }

                if (is_array($value) && $this->isMultipleSelection()) {
                    foreach($value as $val) {
                        if (!is_string($val) || !array_key_exists($val, $options)) {
                            return false;
                        }
                    }
                    
                    return true;
                }
                
                return false;
            },
            errorMessage: 'The :attribute items are invalid.',
        );
    }
}