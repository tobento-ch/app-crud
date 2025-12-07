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
use Tobento\App\Crud\Button\ButtonInterface;
use Tobento\App\Crud\Button\Buttons as Btns;
use Tobento\App\Crud\Button\ButtonsInterface;
use Tobento\App\Crud\Button\ConfigurableButtons;
use Tobento\App\Crud\Url\UrlResolverInterface;
use Tobento\Service\Tag\Attributes;
use Tobento\Service\View\ViewInterface;

class Buttons extends AbstractField
{
    use ConfigurableButtons;
    use Traits\Hidden;
    
    /**
     * @var null|ButtonsInterface
     */
    protected null|ButtonsInterface $buttons = null;
    
    /**
     * @var string
     */
    protected string $align = 'left';
    
    /**
     * @var bool
     */
    protected bool $displayAsField = false;
    
    /**
     * Create a new Html instance.
     *
     * @param string $name
     */
    final public function __construct(
        string $name,
    ) {
        $this->name = $name;
        $this->process('create|edit|index|show', [$this, 'processRender']);
        $this->indexable(false);
        $this->storable(false);
        $this->showable(false);
        $this->configure();
    }
    
    /**
     * Sets the buttons.
     *
     * @param ButtonInterface|ButtonsInterface $buttons
     * @return static $this
     */
    public function buttons(ButtonInterface|ButtonsInterface ...$buttons): static
    {
        $btns = new Btns();
        
        foreach($buttons as $button) {
            if ($button instanceof ButtonInterface) {
                $btns->add($button);
            } else {
                $btns->add(...$button->all());
            }
        }
        
        $this->buttons = $btns;
        return $this;
    }
    
    /**
     * Returns the buttons.
     *
     * @return ButtonsInterface
     */
    public function getButtons(): ButtonsInterface
    {
        if ($this->buttons instanceof ButtonsInterface) {
            return $this->buttons;
        }
        
        return $this->buttons = new Btns();
    }
    
    /**
     * Align to left.
     *
     * @return static $this
     */
    public function alignLeft(): static
    {
        $this->align = 'left';
        return $this;
    }
    
    /**
     * Align to center.
     *
     * @return static $this
     */
    public function alignCenter(): static
    {
        $this->align = 'center';
        return $this;
    }
    
    /**
     * Align to right.
     *
     * @return static $this
     */
    public function alignRight(): static
    {
        $this->align = 'right';
        return $this;
    }
    
    /**
     * Set if to display the message as field.
     *
     * @param bool $field
     * @return static $this
     */
    public function displayAsField(bool $field = true): static
    {
        $this->displayAsField = $field;
        return $this;
    }
    
    /**
     * Processes the store action.
     *
     * @param ActionInterface $action
     * @param Buttons $field
     * @param ViewInterface $view
     * @param UrlResolverInterface $urlResolver
     * @return void
     */
    public function processRender(ActionInterface $action, Buttons $field, ViewInterface $view, UrlResolverInterface $urlResolver): void
    {
        if ($field->isHidden()) {
            $field->html('');
            return;
        }

        $buttons = $urlResolver->resolveButtonsUrl(
            buttons: $this->applyButtonsConfig($field->getButtons()),
            action: $action,
        );
        
        $attributes = new Attributes($field->getAttributes())
            ->add(name: 'class', value: $this->align);
        
        $field->html($view->render(
            view: 'crud/field/buttons',
            data: [
                'field' => $field,
                'buttons' => $buttons,
                'attributes' => $attributes,
                'displayAsField' => $this->displayAsField,
            ],
        ));
    }
}