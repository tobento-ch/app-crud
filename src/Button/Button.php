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
 * Button
 */
final class Button extends AbstractButton
{
    /**
     * Create a new Button.
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
     * Create a new instance.
     *
     * @param string $label
     * @param string $group
     * @param null|string $icon
     * @return static
     */
    public static function new(
        string $label,
        string $group,
        null|string $icon = null,
    ): static {
        return new static($label, $group, $icon);
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
        if ($this->icon) {
            $icon = $view->icon($this->icon);
            $html = (string)$icon->label(Str::esc($this->getLabel()));
            return $this->tag()->withHtml($html)->render();
        }

        return $this->tag()->render();
    }
    
    /**
     * Returns the tag of the button.
     *
     * @return TagInterface
     */
    public function tag(): TagInterface
    {
        $tag = new Tag(
            name: 'button',
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
        
        $tag->attr(name: 'data-button', value: $this->getName());
        
        return $tag;
    }
}