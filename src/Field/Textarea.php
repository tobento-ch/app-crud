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
 * Textarea
 */
class Textarea extends AbstractField
{
    /**
     * Create a new Textarea.
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
        $this->process('index', [$this, 'processIndex']);
        $this->process('create|edit|copy', [$this, 'processCreateEdit']);
        $this->process('store', [$this, 'processStore']);
        $this->process('update', [$this, 'processUpdate']);
        $this->process('show', [$this, 'processShow']);
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
        ViewInterface $view
    ): void {
        $attributes = array_merge(
            $field->getHtmlValidationAttributes(
                $action->name(),
                'textarea',
                $view->trans($field->label())
            ),
            $field->getAttributes(),
        );
        
        $field->html($view->render(
            view: 'crud/field/textarea',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'attributes' => $attributes,
            ],
        ));
    }
    
    /**
     * Processes the index action.
     *
     * @param FieldInterface $field
     * @return void
     */
    public function processIndex(FieldInterface $field): void
    {
        $this->processShow($field);
    }
    
    /**
     * Processes the show action.
     *
     * @param FieldInterface $field
     * @return void
     */
    public function processShow(FieldInterface $field): void
    {
        $html = (string)$field->entity()->get($field->name(), '', $field->locale());
        
        $field->html(nl2br(Str::esc($html)));
    }
}