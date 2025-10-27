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
 * Checkboxes
 */
class Checkboxes extends AbstractField implements OptionsAwareInterface, LiveAwareInterface
{
    use Traits\HasValueFormatter;
    use Traits\Live;
    
    /**
     * @var iterable
     */
    protected iterable $options = [];
    
    /**
     * @var string
     */
    protected string $emptyOption = '_none';
    
    /**
     * @var iterable
     */
    protected iterable $selected = [];
    
    /**
     * Create a new Checkboxes.
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
        $this->process('store:before|update:before', [$this, 'processBeforeSave']);
        $this->process('store|update', [$this, 'processSave']);
        
        // call validate as to add validOptionsRule:
        $this->validate([]);
        
        $this->configure();
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
            resolved: function(Checkboxes $field, mixed $resolved): void {
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
     * Sets the empty option value.
     *
     * @param string $value
     * @return static $this
     */
    public function emptyOption(string $value): static
    {
        $this->emptyOption = $value;
        return $this;
    }
    
    /**
     * Returns the empty option.
     *
     * @return string
     */
    public function getEmptyOption(): string
    {
        return $this->emptyOption;
    }
    
    /**
     * Sets the selected for the given action(s).
     *
     * @param iterable|callable $value
     * @param null|string $action
     * @return static $this
     */
    public function selected(iterable|callable $value, null|string $action = 'create'): static
    {
        if (is_null($action) && is_iterable($value)) {
            $this->selected = $value;
            return $this;
        }
        
        if (is_iterable($value)) {
            $this->resolve(function (Checkboxes $field) use ($value) {
                $field->selected($value, null);
            }, action: $action);
            
            return $this;
        }
        
        $this->resolve(
            resolve: $value,
            resolved: function(Checkboxes $field, mixed $resolved): void {
                if (is_iterable($resolved)) {
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
     * @return array
     */
    public function getSelected(): array
    {
        return Iter::toArray(iterable: $this->selected);
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
     * @param Checkboxes $field
     * @param ViewInterface $view
     * @return void
     */
    public function processIndexAction(ActionInterface $action, Checkboxes $field, ViewInterface $view): void
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
     * @param FieldInterface $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processCreateEdit(
        ActionInterface $action,
        FieldInterface $field,
        ViewInterface $view,
    ): void {
        $form = $view->form();        
        $attributes = $field->getAttributes();
        $attributes['id'] ??= $form->nameToId($field->name());
        $attributes = $this->assignLiveAttributes($action->name(), $attributes);
        $name = $form->nameToArray($field->name());

        $body = $form->checkboxes(
            name: $name,
            items: $field->getOptions(),
            selected: $field->entity()->get($field->name(), $field->getSelected()),
            attributes: $attributes,
            labelAttributes: [],
            withInput: true,
            wrapClass: 'wrap-v',
        );
        
        $body .= $form->input(
            name: $name.'[]',
            type: 'hidden',
            value: $field->getEmptyOption(),
            attributes: ['id' => null],
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
     * Pre processes the store and update action before validation
     * where we remove the empty option value.
     *
     * @param Checkboxes $field
     * @param InputInterface $input
     * @return void
     */
    public function processBeforeSave(
        Checkboxes $field,
        InputInterface $input,
    ): void {
        if (! $input->has($field->name())) {
            return;
        }
        
        $options = $input->get($field->name());
        
        if (!is_array($options)) {
            $input->delete($field->name());
            return;
        }
        
        $options = (new Collection(array_flip($options)));
        
        $options = $options->except([$field->getEmptyOption()]);

        $input->set($field->name(), array_flip($options->all()));
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
        
        $options = $input->get($field->name());
        
        if (!is_array($options)) {
            $input->delete($field->name());
            return;
        }
    }
    
    /**
     * Processes the index table action.
     *
     * @param ActionInterface $action
     * @param Checkboxes $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    protected function processIndexTable(ActionInterface $action, Checkboxes $field, ViewInterface $view): void
    {
        $form = $view->form();        
        $attributes = $field->getAttributes();
        $attributes['id'] ??= $form->nameToId($field->name().'_'.$field->entity()->id());
        $attributes['tabindex'] = '5';
        $name = $form->nameToArray($field->name());
        
        $html = '<div class="field list">';
        $html .= $form->checkboxes(
            name: $name,
            items: $field->getOptions(),
            selected: $field->entity()->get($field->name(), $field->getSelected()),
            attributes: $attributes,
            labelAttributes: [],
            withInput: true,
            wrapClass: 'wrap-v',
        );
        $html .= $form->input(
            name: $name.'[]',
            type: 'hidden',
            value: $field->getEmptyOption(),
            attributes: ['id' => null],
        );
        $html .= '</div>';
        
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

                if (is_array($value)) {
                    foreach($value as $val) {
                        if (!is_scalar($val) || !array_key_exists($val, $options)) {
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