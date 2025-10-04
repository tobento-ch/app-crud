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
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\Support\Str;
use Tobento\Service\View\ViewInterface;

/**
 * Text
 */
class Text extends AbstractField
{
    use Traits\HasValueFormatter;
    
    /**
     * @var string
     */
    protected string $inputType = 'text';

    /**
     * @var null|string|array
     */
    protected null|string|array $value = null;
    
    /**
     * @var string|array
     */
    protected string|array $defaultValue = '';
    
    /**
     * Create a new Text.
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
        $this->process('create|edit|copy', [$this, 'processCreateEdit']);
        $this->process('store', [$this, 'processStore']);
        $this->process('update', [$this, 'processUpdate']);
        $this->process('show', [$this, 'processShowText']);
        $this->configure();
    }

    /**
     * Sets the value.
     *
     * @param string|array $value
     * @return static $this
     */
    public function value(string|array $value): static
    {
        $this->value = $value;
        return $this;
    }
    
    /**
     * Returns the value for the field.
     *
     * @param Text $field
     * @param null|string $locale
     * @param null|string $action
     * @return string
     */
    public function getValue(Text $field, null|string $locale = null, null|string $action = null): string|Stringable
    {
        $value = match (true) {
            !is_null($this->value) => !is_null($locale) ? $this->value[$locale] ?? $this->value : $this->value,
            !is_null($locale) => $field->entity()->get($field->name(), $field->getDefaultValue($locale), $locale),
            default => $field->entity()->get($field->name(), $field->getDefaultValue()),
        };
        
        if ($action && $this->hasValueFormatter(action: $action)) {
            return $this->formattingValue(action: $action, value: $value, field: $field);
        }
        
        return is_scalar($value) ? (string)$value : '';
    }
    
    /**
     * Sets the default value.
     *
     * @param string|array $value
     * @return static $this
     */
    public function defaultValue(string|array $value): static
    {
        $this->defaultValue = $value;
        return $this;
    }
    
    /**
     * Returns the default value.
     *
     * @param null|string $locale
     * @return string
     */
    public function getDefaultValue(null|string $locale = null): string
    {
        if (!is_null($locale)) {
            $value = $this->defaultValue[$locale] ?? $this->defaultValue;
            return is_string($value) ? $value : '';
        }
        
        return is_string($this->defaultValue) ? $this->defaultValue : '';
    }
    
    /**
     * Sets the input type.
     *
     * @param string $type
     * @return static $this
     */
    public function type(string $type): static
    {
        $this->inputType = $type;
        return $this;
    }
    
    /**
     * Returns the type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->inputType;
    }
    
    /**
     * Processes the create and edit action.
     *
     * @param ActionInterface $action
     * @param Text $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processCreateEdit(
        ActionInterface $action,
        Text $field,
        ViewInterface $view
    ): void {
        if ($field->getType() === 'hidden') {
            $field->html($view->render(
                view: 'crud/field/text-hidden',
                data: [
                    'field' => $field,
                    'entity' => $field->entity(),
                    'actionName' => $action->name(),
                    'inputType' => $field->getType(),
                    'inputAttributes' => $field->getAttributes(),
                ],
            ));
            
            return;
        }
        
        $attributes = array_merge(
            $field->getHtmlValidationAttributes(
                $action->name(),
                $field->getType(),
                $view->trans($field->label())
            ),
            $field->getAttributes(),
        );
        
        $field->html($view->render(
            view: 'crud/field/text',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'inputType' => $field->getType(),
                'inputAttributes' => $attributes,
            ],
        ));
    }
    
    /**
     * Processes the show action.
     *
     * @param Text $field
     * @param ViewInterface $view
     * @return void
     */
    public function processShowText(Text $field, ViewInterface $view): void
    {
        $field->html($view->render(
            view: 'crud/field/show/text',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
            ],
        ));
    }
    
    /**
     * Processes the index action.
     *
     * @param ActionInterface $action
     * @param Text $field
     * @param ViewInterface $view
     * @return void
     */
    public function processIndexAction(ActionInterface $action, Text $field, ViewInterface $view): void
    {
        if ($this->isTableEditable()) {
            $this->processIndexTable($action, $field, $view);
            return;
        }
        
        $field->html(Str::esc($field->getValue(field: $field, locale: $field->locale(), action: 'index')));
    }
    
    /**
     * Processes the index table action.
     *
     * @param ActionInterface $action
     * @param Text $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    protected function processIndexTable(ActionInterface $action, Text $field, ViewInterface $view): void
    {
        $attributes = array_merge(
            $field->getHtmlValidationAttributes(
                $action->name(),
                $field->getType(),
                $view->trans($field->label())
            ),
            $field->getAttributes(),
        );
        $attributes['id'] = '';
        $attributes['tabindex'] = '5';
        
        $form = $view->form();
        
        if (! $field->isTranslatable()) {
            $html = $form->input(
                name: $field->name(),
                type: $field->getType(),
                value: $field->getValue($field),
                attributes: $attributes,
            );

            $field->html($html);
            return;
        }
        
        $html = '';
        
        foreach($field->locales() as $locale => $name) {
            $html .= '<div class="mb-xs">';
            $html .= '<div class="mb-xxs">'.$view->esc($name).'</div>';
            $html .= $form->input(
                name: $field->name().'.'.$locale,
                type: $field->getType(),
                value: $field->getValue($field, $locale),
                attributes: $attributes,
            );
            $html .= '</div>';
        }
        
        $field->html($html);
    }
}