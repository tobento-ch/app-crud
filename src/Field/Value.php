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
use Tobento\Service\Support\Str;
use Tobento\Service\View\ViewInterface;

/**
 * Value
 */
class Value extends AbstractField
{
    use Traits\HasValueFormatter;
    
    /**
     * @var mixed
     */
    protected mixed $value = null;
    
    /**
     * Create a new Value.
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
        $this->process('store:before|update:before', [$this, 'processBeforeSave']);
        $this->process('show', [$this, 'processShow']);
        $this->indexable(false);
        $this->showable(false);
        $this->configure();
    }
    
    /**
     * Sets the value.
     *
     * @param mixed $value
     * @return static $this
     */
    public function value(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }
    
    /**
     * Returns the value.
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }
    
    /**
     * Processes the index action.
     *
     * @param FieldInterface $field
     * @return void
     */
    public function processIndexAction(FieldInterface $field): void
    {
        if (! $this->hasValueFormatter(action: 'index')) {
            $this->formatValue(formatter: new Field\Formatter\Str(trimWidth: 100, arrayToJson: true), action: 'index');
        }
        
        $value = $field->entity()->get(name: $field->name(), locale: $field->locale());
        
        $value = $this->formattingValue(action: 'index', value: $value, field: $field);
        
        $field->html(Str::esc($value));
    }
    
    /**
     * Processes the before save action.
     *
     * @param Value $field
     * @param InputInterface $input
     * @return void
     */
    public function processBeforeSave(
        Value $field,
        InputInterface $input,
    ): void {
        $input->set($field->name(), $field->getValue());
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
            $this->formatValue(formatter: new Field\Formatter\Str(trimWidth: 100, arrayToJson: true), action: 'show');
        }
        
        $value = $field->entity()->get(name: $field->name(), locale: $field->locale());
        
        $value = $this->formattingValue(action: 'show', value: $value, field: $field);

        $field->html($view->render(
            view: 'crud/field/show/field',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'formatter' => fn (mixed $value) => $this->formattingValue(action: 'show', value: $value, field: $field),
                'renderLabel' => true,
                'text' => $value,
            ],
        ));
    }
}