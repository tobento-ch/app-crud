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
 * Textarea
 */
class Textarea extends AbstractField implements LiveAwareInterface
{
    use Traits\HasValueFormatter;
    use Traits\Hidden;
    use Traits\Live;
    use Traits\HasMachineTranslator;
    
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
        if ($field->isHidden()) {
            $field->html('');
            return;
        }
        
        $attributes = array_merge(
            $field->getHtmlValidationAttributes(
                $action->name(),
                'textarea',
                $view->trans($field->label())
            ),
            $field->getAttributes(),
        );
        
        $attributes = $this->assignLiveAttributes($action->name(), $attributes);
        
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

        if (! $this->hasValueFormatter(action: 'index')) {
            $this->formatValue(formatter: new Field\Formatter\Str(trimWidth: 100), action: 'index');
        }
        
        $value = $field->entity()->get($field->name(), '', $field->locale());
        
        $value = $this->formattingValue(action: 'index', value: $value, field: $field);
        
        $field->html(nl2br(Str::esc($value)));
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
            
            $html .= '<div class="mb-xxs">'.$view->esc($name);
            
            if ($field->hasMachineTranslator()) {
                $html .= $field->renderMachineTranslator(
                    toField: $field->name().'.'.$locale,
                    view: $view
                );
            }
            
            $html .= '</div>';
            
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
        $value = $field->entity()->get($field->name(), '', $field->locale());
        
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