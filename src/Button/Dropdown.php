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

use Tobento\Service\Tag\TagInterface;
use Tobento\Service\Tag\Tag;
use Tobento\Service\Tag\Attributes;
use Tobento\Service\Tag\Str;
use Tobento\Service\View\ViewInterface;

/**
 * Dropdown
 */
final class Dropdown extends AbstractButton implements ButtonsAwareInterface
{
    /**
     * @var null|ButtonsInterface
     */
    private null|ButtonsInterface $buttons = null;
    
    /**
     * Create a new Dropdown.
     *
     * @param string $label
     * @param string $group
     * @param null|string $icon
     */
    public function __construct(
        string $label,
        string $group,
        null|string $icon = null,
    ) {
        $this->label = $label;
        $this->group = $group;
        $this->icon = $icon;
        $this->attributes = new Attributes();
    }
    
    /**
     * Sets the buttons.
     *
     * @param ButtonInterface $buttons
     * @return static $this
     */
    public function buttons(ButtonInterface ...$buttons): static
    {
        $this->buttons = new Buttons(...$buttons);
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
        return $new;
    }
    
    /**
     * Returns the buttons.
     *
     * @return ButtonsInterface
     */
    public function getButtons(): ButtonsInterface
    {
        return $this->buttons ?: new Buttons();
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
        if ($this->getButtons()->count() === 0) {
            return '';
        }
            
        $menuId = sprintf('dropdown-menu-%s%s', $this->getName(), (string)$this->entity?->id());
        $menuId = preg_replace('/[^a-zA-Z0-9-_]/', '', $menuId);
        
        $tag = $this->tag(menuId: $menuId);
        
        if ($this->icon) {
            $icon = $view->icon($this->icon);
            $html = (string)$icon->label(Str::esc($this->getLabel()));
            $tag = $tag->withHtml($html);
        }
        
        $html = '<div class="crud-dropdown" data-dropdown="'.Str::esc($menuId).'">';
        $html .= $tag->render();
        $html .= '<div class="crud-dropdown-menu" id="'.Str::esc($menuId).'" role="menu">';
        $html .= '<div class="crud-dropdown-body">';
        
        foreach ($this->getButtons() as $button) {
            $html .= '<div class="crud-dropdown-item">';
            $html .= $button->render($view);
            $html .= '</div>';
        }
        
        $html .= '</div></div></div>';
        
        return $html;
    }
    
    /**
     * Returns the tag of the button.
     *
     * @param $menuId
     * @return TagInterface
     */
    private function tag(string $menuId): TagInterface
    {
        $tag = new Tag(
            name: 'span', // do not use button, as it could be inside a form and will be submitted!
            html: Str::esc($this->getLabel()),
            attributes: $this->attributes,
        );
        
        if (! $tag->attributes()->has('class')) {
            $tag->class(value: 'button text-xs');
        }
        
        if ($this->raw) {
            $tag->class(value: 'raw');
        } elseif ($this->primary) {
            $tag->class(value: 'primary');
        }
        
        $tag->attr(name: 'aria-haspopup', value: 'true');
        $tag->attr(name: 'aria-controls', value: $menuId);
        $tag->attr(name: 'data-button', value: $this->getName());
        
        return $tag;
    }
}