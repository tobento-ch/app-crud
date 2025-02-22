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
use Tobento\Service\Support\Str;
use Tobento\Service\View\ViewInterface;

/**
 * Value
 */
class Value extends AbstractField
{
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
        $value = $field->entity()->get($field->name());
        
        if (is_scalar($value)) {
            $field->html(Str::esc((string)$value));
            return;
        }
        
        if (is_array($value)) {
            $value = json_encode($value);
            $value = mb_strimwidth($value, 0, 100, '...');
            $field->html(Str::esc($value));
            return;
        }
        
        $field->html('');
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
}