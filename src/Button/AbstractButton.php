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

use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Url\HasLinksTo;
use Tobento\Service\View\ViewInterface;
use Tobento\Service\Tag\AttributesInterface;
use Tobento\Service\Tag\Attributes;

/**
 * AbstractButton
 */
abstract class AbstractButton implements ButtonInterface
{
    use HasLinksTo;
        
    /**
     * @var string
     */
    protected string $url = '';

    /**
     * @var null|string
     */
    protected null|string $name = null;
    
    /**
     * @var string
     */
    protected string $label;
    
    /**
     * @var string
     */
    protected string $group;
    
    /**
     * @var null|string
     */
    protected null|string $icon = null;
    
    /**
     * @var bool
     */
    protected bool $primary = false;
    
    /**
     * @var bool
     */
    protected bool $raw = false;
    
    /**
     * @var AttributesInterface
     */
    protected AttributesInterface $attributes;
    
    /**
     * @var null|EntityInterface
     */
    protected $entity = null;

    /**
     * Sets the name.
     *
     * @param string $name
     * @return static $this
     */
    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name ?: $this->getLabel();
    }

    /**
     * Sets the label.
     *
     * @param string $label
     * @return static $this
     */
    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }
    
    /**
     * Returns the label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }
    
    /**
     * Sets the group.
     *
     * @param string $group
     * @return static $this
     */
    public function group(string $group): static
    {
        $this->group = $group;
        return $this;
    }
    
    /**
     * Returns the group.
     *
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }
    
    /**
     * Sets the icon.
     *
     * @param string $icon
     * @return static $this
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }
    
    /**
     * Returns the icon.
     *
     * @return null|string
     */
    public function getIcon(): null|string
    {
        return $this->icon;
    }
    
    /**
     * Sets a button as primary.
     *
     * @param bool $primary
     * @return static $this
     */
    public function primary(bool $primary = true): static
    {
        $this->primary = $primary;
        return $this;
    }
    
    /**
     * Sets a button as raw.
     *
     * @param bool $raw
     * @return static $this
     */
    public function raw(bool $raw = true): static
    {
        $this->raw = $raw;
        return $this;
    }
    
    /**
     * Sets a button attribute.
     *
     * @param string $name
     * @param mixed $value
     * @return static $this
     */
    public function attr(string $name, mixed $value = null): static
    {
        $this->attributes->set($name, $value);
        return $this;
    }
    
    /**
     * Removes a button attribute.
     *
     * @param string $name
     * @return static $this
     */
    public function removeAttr(string $name): static
    {
        $attributes = $this->attributes->all();
        unset($attributes[$name]);
        $this->attributes = new Attributes($attributes);
        return $this;
    }
    
    /**
     * Sets whether to ask confirmation for the button action.
     *
     * @param bool|string $text
     * @return static $this
     */
    public function askConfirmation(bool|string $text = true): static
    {
        if (is_string($text)) {
            $this->attr('data-confirm', $text);
            return $this;
        }
        
        if ($text === true) {
            $this->attr('data-confirm', '');
            return $this;
        }
        
        $this->removeAttr('data-confirm');
        return $this;
    }
    
    /**
     * Sets whether to use ajax to perform the button action.
     *
     * @param bool|string $text
     * @return static $this
     */
    public function ajaxAction(bool|string $text = true): static
    {
        if (is_string($text)) {
            $this->attr('data-button-ajax', $text);
            return $this;
        }
        
        if ($text === true) {
            $this->attr('data-button-ajax', '');
            return $this;
        }
        
        $this->removeAttr('data-button-ajax');
        return $this;
    }

    /**
     * Returns a new instance with the specified url.
     *
     * @param string $url
     * @return static
     */
    public function withUrl(string $url): static
    {
        $new = clone $this;
        $new->url = $url;
        return $new;
    }
    
    /**
     * Returns the url.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }
    
    /**
     * Returns a new instance with the specified entity.
     *
     * @param EntityInterface $entity
     * @return static
     */
    public function withEntity(EntityInterface $entity): static
    {
        $new = clone $this;
        $new->entity = $entity;
        return $new;
    }
    
    /**
     * Clone button.
     */
    public function __clone()
    {
        $this->attributes = clone $this->attributes;
    }
    
    /**
     * Returns the html of the button. MUST be escaped.
     *
     * @param ViewInterface $view
     * @return string
     */
    abstract public function render(ViewInterface $view): string;
}