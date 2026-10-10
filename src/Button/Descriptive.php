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

namespace Tobento\App\Crud\Button;

use Stringable;
use Tobento\Service\Tag\Attributes;
use Tobento\Service\View\ViewInterface;

/**
 * Descriptive
 */
final class Descriptive extends AbstractButton implements ButtonsAwareInterface
{
    /**
     * @var null|ButtonsInterface
     */
    private null|ButtonsInterface $buttons = null;
    
    /**
     * Create a new Descriptive instance.
     *
     * @param ButtonInterface $button
     * @param string|Stringable $description
     * @param null|string $visualGroup
     */
    public function __construct(
        private ButtonInterface $button,
        private string|Stringable $description = '',
        private null|string $visualGroup = null
    ) {
        $this->label = $button->getLabel();
        $this->group = $button->getGroup();
        $this->icon = $button->getIcon();
        $this->name = $button->getName();
        $this->attributes = new Attributes();
        
        $this->buttons($button);
    }
    
    /**
     * Sets the buttons.
     *
     * @param ButtonInterface ...$buttons
     * @return static $this
     */
    public function buttons(ButtonInterface ...$buttons): static
    {
        $this->buttons = new Buttons(...$buttons);
        $this->button = $buttons[0] ?? $this->button;
        return $this;
    }
    
    /**
     * Returns a new instance with the given buttons.
     *
     * @param ButtonsInterface $buttons
     * @return static
     */
    public function withButtons(ButtonsInterface $buttons): static
    {
        $new = clone $this;
        $new->buttons = $buttons;
        $new->button = $buttons->first() ?? $this->button;
        return $new;
    }
    
    /**
     * Returns the buttons.
     *
     * @return ButtonsInterface
     */
    public function getButtons(): ButtonsInterface
    {
        return $this->buttons ?? new Buttons();
    }
    
    /**
     * Returns the button rendered inside the descriptive block.
     *
     * @return ButtonInterface
     */
    public function getButton(): ButtonInterface
    {
        return $this->button;
    }

    /**
     * Returns the descriptive text shown below the label.
     *
     * @return string|Stringable
     */
    public function getDesc(): string|Stringable
    {
        return $this->description;
    }

    /**
     * Returns the visual group name for this descriptive item.
     *
     * @return null|string
     */
    public function getVisualGroup(): null|string
    {
        return $this->visualGroup;
    }

    /**
     * Returns the html of the button. MUST be escaped.
     *
     * @param ViewInterface $view
     * @return string
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function render(ViewInterface $view): string
    {
        if ($this->getButtons()->first() === null) {
            return '';
        }
        
        // Render the inner button
        if ($this->getDesc() === '') {
            return $this->getButton()->render($view);
        }

        $html = '<div class="crud-btn-descriptive" data-button="">';
        $html .= '<div class="descriptive-btn">'.$this->getButton()->render($view).'</div>';
        $html .= '<div class="descriptive-desc">'.$view->esc($this->getDesc()).'</div>';
        $html .= '</div>';

        return $html;
    }
}