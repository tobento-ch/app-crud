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

use Tobento\Service\Tag\NullTag;
use Tobento\Service\Tag\TagInterface;
use Tobento\Service\Tag\Tag;
use Tobento\Service\Tag\Attributes;
use Tobento\Service\Tag\Str;
use Tobento\Service\View\ViewInterface;

/**
 * Link
 */
final class Link extends AbstractButton
{
    /**
     * Create a new Link.
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
        if ($this->url === '') {
            return new NullTag();
        }
        
        $tag = (new Tag(
            name: 'a',
            html: Str::esc($this->getLabel()),
            attributes: $this->attributes,
        ))->attr(name: 'href', value: $this->url);
        
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