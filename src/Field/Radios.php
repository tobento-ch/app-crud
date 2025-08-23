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
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\View\ViewInterface;
use Tobento\Service\Iterable\Iter;
use Tobento\Service\Collection\Collection;
use Tobento\Service\Support\Str;
use Tobento\Service\Validation\ValidatorInterface;
use Tobento\Service\Validation\Rule\Passes;
use InvalidArgumentException;

/**
 * Radios
 */
class Radios extends AbstractField implements OptionsAwareInterface
{
    use Traits\HasValueFormatter;
    
    /**
     * @var iterable
     */
    protected iterable $options = [];
    
    /**
     * @var null|string
     */
    protected null|string $selected = null;
    
    /**
     * @var bool
     */
    protected bool $displayInline = false;
    
    /**
     * Create a new Radios.
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
            resolved: function(Radios $field, mixed $resolved): void {
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
     * Sets the selected for the given action(s).
     *
     * @param string|callable $value
     * @param null|string $action
     * @return static $this
     */
    public function selected(string|callable $value, null|string $action = 'create'): static
    {
        if (is_null($action) && is_string($value)) {
            $this->selected = $value;
            return $this;
        }
        
        if (is_string($value)) {
            $this->resolve(function (Radios $field) use ($value) {
                $field->selected($value, null);
            }, action: $action);
            
            return $this;
        }
        
        $this->resolve(
            resolve: $value,
            resolved: function(Radios $field, mixed $resolved): void {
                if (is_string($resolved)) {
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
     * @return null|string
     */
    public function getSelected(): null|string
    {
        return $this->selected;
    }
    
    /**
     * Sets if to display the radios inline.
     *
     * @param bool $inline
     * @return static $this
     */
    public function displayInline(bool $inline = true): static
    {
        $this->displayInline = $inline;
        return $this;
    }
    
    /**
     * Returns whether to display inline.
     *
     * @return bool
     */
    public function getDisplayInline(): bool
    {
        return $this->displayInline;
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
     * Processes the index action.
     *
     * @param ActionInterface $action
     * @param Radios $field
     * @param ViewInterface $view
     * @return void
     */
    public function processIndexAction(ActionInterface $action, Radios $field, ViewInterface $view): void
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
        if (! $this->hasValueFormatter(action: 'index')) {
            $this->formatValue(formatter: new Field\Formatter\Str(trimWidth: 100), action: 'index');
        }
        
        $value = $this->formattingValue(action: 'index', value: $field->entity()->get($field->name()), field: $field);
        $field->html(Str::esc($value));
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
        if (! $this->hasValueFormatter(action: 'show')) {
            $this->formatValue(formatter: new Field\Formatter\Str(), action: 'show');
        }
        
        $value = $this->formattingValue(action: 'show', value: $field->entity()->get($field->name()), field: $field);

        $field->html($view->render(
            view: 'crud/field/show/field',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'renderLabel' => true,
                'text' => $value,
            ],
        ));
    }
        
    /**
     * Processes the create and edit action.
     *
     * @param ActionInterface $action
     * @param Radios $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processCreateEdit(
        ActionInterface $action,
        Radios $field,
        ViewInterface $view,
    ): void {
        $form = $view->form();        
        $attributes = $field->getAttributes();
        $attributes['id'] ??= $form->nameToId($field->name());
        $name = $form->nameToArray($field->name());
        
        $selected = $field->entity()->get($field->name(), $field->getSelected());
        
        if (is_bool($selected)) {
            $selected = $selected === false ? '0' : '1';
        }
        
        if (!is_scalar($selected)) {
            $selected = '';
        }
        
        $body = $form->radios(
            name: $name,
            items: $field->getOptions(),
            selected: (string)$selected,
            attributes: $attributes,
            labelAttributes: [],
            withInput: true,
            wrapClass: $field->getDisplayInline() ? 'wrap-h' : 'wrap-v',
        );
        
        $field->html($view->render(
            view: 'crud/field/select',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'body' => $body,
                'hasLabels' => true,
            ],
        ));
    }
    
    /**
     * Processes the store and update action.
     *
     * @param FieldInterface $field
     * @param InputInterface $input
     * @return void
     */
    public function processSave(
        FieldInterface $field,
        InputInterface $input,
    ): void {
        if (! $input->has($field->name())) {
            return;
        }
        
        $value = $input->get($field->name());
        
        if (!is_string($value)) {
            $input->delete($field->name());
            return;
        }
    }
    
    /**
     * Processes the index table action.
     *
     * @param ActionInterface $action
     * @param Radios $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    protected function processIndexTable(ActionInterface $action, Radios $field, ViewInterface $view): void
    {
        $form = $view->form();        
        $attributes = $field->getAttributes();
        $attributes['id'] ??= $form->nameToId($field->name().'_'.$field->entity()->id());
        $attributes['tabindex'] = '5';
        $name = $form->nameToArray($field->name());
        
        $selected = $field->entity()->get($field->name(), $field->getSelected());
        
        if (is_bool($selected)) {
            $selected = $selected === false ? '0' : '1';
        }
        
        if (!is_scalar($selected)) {
            $selected = '';
        }
        
        $html = $form->select(
            name: $name,
            items: $field->getOptions(),
            selected: (string)$selected,
            selectAttributes: $attributes,
            withInput: true,
        );
        
        $field->html($html);
    }
    
    /**
     * Returns the valid options rule.
     *
     * @return Passes
     * @psalm-suppress InvalidScalarArgument
     */
    protected function validOptionsRule(): Passes
    {
        return new Passes(
            passes: function(mixed $value): bool {
                $options = $this->getOptions();

                if (is_scalar($value) && array_key_exists($value, $options)) {
                    return true;
                }
                
                return false;
            },
            errorMessage: 'The :attribute items are invalid.',
        );
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
        if (is_bool($value)) {
            $value = $value === false ? '0' : '1';
        }
        
        if (!isset($this->valueFormatters[$action])) {
            return $value;
        }
        
        return ($this->valueFormatters[$action])($value, $field);
    }
}