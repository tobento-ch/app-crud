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

use Closure;
use Stringable;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\Service\Tag\TagInterface;
use Tobento\Service\Tag\Tag;
use Tobento\Service\Tag\AttributesInterface;
use Tobento\Service\Tag\Attributes;
use Tobento\Service\Tag\Str;
use Tobento\Service\View\ViewInterface;

final class Html extends AbstractButton
{
    private string|Stringable|Closure $html = '';
    
    /**
     * Create a new Form instance.
     *
     * @param string $group
     * @param null|string $icon
     */
    public function __construct(
        string $group,
    ) {
        $this->label = '';
        $this->group = $group;
        $this->icon = null;
        $this->attributes = new Attributes();
    }
    
    /**
     * Create a new instance.
     *
     * @param string $group
     * @return static
     */
    public static function new(
        string $group,
    ): static {
        return new static($group);
    }
    
    /**
     * Sets the html. MUST be escaped.
     *
     * @param string|Stringable|Closure $html
     * @return static $this
     */
    public function html(string|Stringable|Closure $html): static
    {
        $this->html = $html;
        return $this;
    }
    
    /**
     * Returns the entity or null if none.
     *
     * @return null|EntityInterface
     */
    public function getEntity(): null|EntityInterface
    {
        return $this->entity;
    }
    
    /**
     * Returns the attributes.
     *
     * @return AttributesInterface
     */
    public function getAttributes(): AttributesInterface
    {
        return $this->attributes;
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
        if (is_callable($this->html)) {
            return call_user_func($this->html, $this, $view);
        }
        
        return (string)$this->html;
    }
}