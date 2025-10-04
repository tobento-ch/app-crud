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
use Tobento\Service\Tag\AttributesInterface;
use Tobento\Service\Tag\Attributes;
use Tobento\Service\Tag\Str;
use Tobento\Service\View\ViewInterface;

final class Form extends AbstractButton
{
    /**
     * @var string
     */
    private string $method = 'POST';
    
    /**
     * @var bool
     */
    private bool $renderEmptyUrl = false;
    
    /**
     * Create a new Form instance.
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
     * Sets the form method.
     *
     * @param string $name
     * @return static $this
     */
    public function method(string $name): static
    {
        $this->method = $name;
        return $this;
    }
    
    /**
     * Set whether to render the form if the resolved url is an empty string.
     *
     * @param bool $render
     * @return static $this
     */
    public function renderEmptyUrl(bool $render = true): static
    {
        $this->renderEmptyUrl = $render;
        return $this;
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
        if ($this->renderEmptyUrl === false && $this->url === '') {
            return '';
        }
        
        if (is_null($this->entity)) {
            return '';
        }
        
        $tag = $this->tag();
        
        if ($this->icon) {
            $icon = $view->icon($this->icon);
            $html = (string)$icon->label(Str::esc($this->getLabel()));
            $tag = $tag->withHtml($html);
        }
        
        $form = $view->form();
        
        $html = $form->form(['action' => $this->url, 'method' => $this->method])
            .$form->input(name: 'id', type: 'hidden', value: (string)$this->entity->id())
            .$tag->render()
            .$form->close();
        
        return $html;
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