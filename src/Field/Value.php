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
     * Create a new Text.
     *
     * @param string $name
     */
    final public function __construct(
        string $name,
    ) {
        $this->name = $name;
        $this->process('store|update', [$this, 'processSave']);
        $this->indexable(false);
        $this->configure();
    }

    /**
     * Create a new instance.
     *
     * @param string $name
     * @param null|string $label
     * @return static
     */
    public static function new(string $name): static
    {
        return new static($name);
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
     * Processes the store action.
     *
     * @param Value $field
     * @param InputInterface $input
     * @return void
     */
    public function processSave(
        Value $field,
        InputInterface $input,
    ): void {
        $input->set($field->name(), $field->getValue());
    }
}