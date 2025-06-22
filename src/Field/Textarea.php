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
        $this->process('index', [$this, 'processIndexAction']);
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
     * @param ActionInterface $action
     * @param Textarea $field
     * @param ViewInterface $view
     * @return void
     */
    public function processIndexAction(ActionInterface $action, Textarea $field, ViewInterface $view): void
    {
        if ($this->isTableEditable()) {
            $this->processIndexTable($action, $field, $view);
            return;
        }
        
        $text = (string)$field->entity()->get($field->name(), '', $field->locale());
        $text = mb_strimwidth($text, 0, 100, '...');
        
        $field->html(nl2br(Str::esc($text)));
    }
    
    /**
     * Processes the index table action.
     *
     * @param ActionInterface $action
     * @param Textarea $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    protected function processIndexTable(ActionInterface $action, Textarea $field, ViewInterface $view): void
    {
        $attributes = array_merge(
            $field->getHtmlValidationAttributes(
                $action->name(),
                'textarea',
                $view->trans($field->label())
            ),
            $field->getAttributes(),
        );
        $attributes['id'] = '';
        $attributes['tabindex'] = '5';
        
        $entity = $action->entity();
        
        $form = $view->form();
        
        if (! $field->isTranslatable()) {
            $html = $form->textarea(
                name: $field->name(),
                value: $entity->get($field->name(), ''),
                attributes: $attributes,
            );

            $field->html($html);
            return;
        }
        
        $html = '';
        
        foreach($field->locales() as $locale => $name) {
            $html .= '<div class="mb-xs">';
            $html .= '<div class="mb-xxs">'.$view->esc($name).'</div>';
            $html .= $form->textarea(
                name: $field->name().'.'.$locale,
                value: $entity->get($field->name(), '', $locale),
                attributes: $attributes,
            );
            $html .= '</div>';
        }
        
        $field->html($html);
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
        $text = (string)$field->entity()->get($field->name(), '', $field->locale());
        
        $field->html($view->render(
            view: 'crud/field/show/field',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'renderLabel' => true,
                'text' => $text,
            ],
        ));
    }
}