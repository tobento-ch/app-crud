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

namespace Tobento\App\Crud\Html;

use Stringable;
use Tobento\Service\Support\Htmlable;
use Tobento\Service\Support\Str;
use Tobento\Service\Tag\Attributes;

class Message implements Htmlable, Stringable
{
    /**
     * @var string
     */
    protected string $html = '';
    
    /**
     * @var null|string
     */
    protected null|string $type = null;
    
    /**
     * @var null|string
     */
    protected null|string $summary = null;
    
    /**
     * @var bool
     */
    protected bool $open = false;
    
    /**
     * @var null|string|Stringable
     */
    protected null|string|Stringable $icon = null;
    
    /**
     * @var array
     */
    protected array $attributes = [];
    
    /**
     * @var bool
     */
    protected bool $displayAsField = false;
    
    /**
     * Create a new instance.
     *
     * @param string|Stringable $title
     * @param string|Stringable $text
     * @param array $list
     * @param null|string $summary
     * @param bool $open
     * @param null|string|Stringable $icon
     * @param bool $success
     * @param bool $warning
     * @param bool $danger
     * @param bool $info
     * @param array $attributes
     * @param bool displayAsField
     */
    public function __construct(
        string|Stringable $title = '',
        string|Stringable $text = '',
        array $list = [],
        array $keyedList = [],
        null|string $summary = null,
        bool $open = false,
        null|string|Stringable $icon = null,
        bool $success = false,
        bool $warning = false,
        bool $danger = false,
        bool $info = false,
        array $attributes = [],
        bool $displayAsField = false,
    ) {
        $this->title($title);
        $this->text($text);
        $this->list($list);
        $this->keyedList($keyedList);
        
        if ($summary) {
            $this->summary($summary);
        }
        
        $this->open($open);
        
        if ($icon) {
            $this->icon($icon);
        }
        
        $this->attributes($attributes);
        $this->displayAsField($displayAsField);
        
        if ($success) {
            $this->success();
        }
        
        if ($warning) {
            $this->warning();
        }
        
        if ($danger) {
            $this->danger();
        }
        
        if ($info) {
            $this->info();
        }
    }

    /**
     * Sets a title.
     *
     * @param string|Stringable $title
     * @param array $attributes
     * @return static $this
     */
    public function title(string|Stringable $title, array $attributes = []): static
    {
        if ($title instanceof Htmlable) {
            $this->html .= $title->toHtml();
            return $this;
        }
        
        $title = (string)$title;
        
        if ($title === '') {
            return $this;
        }
        
        if (empty($attributes)) {
            $attributes = ['class' => 'title text-s'];
        }
        
        $attributes = new Attributes($attributes);
        
        $this->html .= '<h3'.(string)$attributes.'>';
        $this->html .= Str::esc($title);
        $this->html .= '</h3>';
        return $this;
    }
    
    /**
     * Sets a text.
     *
     * @param string|Stringable $text
     * @param array $attributes
     * @return static $this
     */
    public function text(string|Stringable $text, array $attributes = []): static
    {
        if ($text instanceof Htmlable) {
            $this->html .= $text->toHtml();
            return $this;
        }
        
        $text = (string)$text;

        if ($text === '') {
            return $this;
        }
        
        if (empty($attributes)) {
            $attributes = ['class' => 'text-xs'];
        }
        
        $attributes = new Attributes($attributes);
        
        $this->html .= '<p'.(string)$attributes.'>';
        $this->html .= Str::esc($text);
        $this->html .= '</p>';
        return $this;
    }
    
    /**
     * Sets a list.
     *
     * @param array $items
     * @param array $attributes
     * @return static $this
     */
    public function list(array $items, array $attributes = []): static
    {
        if (empty($items)) {
            return $this;
        }
        
        if (empty($attributes)) {
            $attributes = ['class' => 'bulleted text-xs'];
        }
        
        $attributes = new Attributes($attributes);
        
        $this->html .= '<ul'.(string)$attributes.'>';
        
        foreach($items as $item) {
            if (!is_scalar($item) && !$item instanceof Stringable) {
                continue;
            }
            
            $this->html .= '<li>';
            $this->html .= Str::esc($item);
            $this->html .= '</li>';
        }
        
        $this->html .= '</ul>';
        return $this;
    }
    
    /**
     * Sets a list.
     *
     * @param array $items
     * @param array $attributes
     * @return static $this
     */
    public function keyedList(array $items, array $attributes = []): static
    {
        if (empty($items)) {
            return $this;
        }
        
        if (empty($attributes)) {
            $attributes = ['class' => 'text-xs'];
        }
        
        $attributes = new Attributes($attributes);
        
        $this->html .= '<div'.(string)$attributes.'>';
        
        foreach($items as $key => $item) {
            if (!is_scalar($item) && !$item instanceof Stringable) {
                continue;
            }
            
            $this->html .= '<div class="title">';
            $this->html .= Str::esc($key);
            $this->html .= '</div>';
            
            $this->html .= '<div class="mb-s">';
            $this->html .= Str::esc($item);
            $this->html .= '</div>';
        }
        
        $this->html .= '</div>';
        
        return $this;
    }
    
    /**
     * Sets a summary text.
     *
     * @param string $text
     * @return static $this
     */
    public function summary(string $text): static
    {
        $this->summary = $text;
        return $this;
    }
    
    /**
     * Sets whether is open or not.
     *
     * @param bool $open
     * @return static $this
     */
    public function open(bool $open = true): static
    {
        $this->open = $open;
        return $this;
    }
    
    /**
     * Sets an icon.
     *
     * @param string|Stringable $icon
     * @return static $this
     */
    public function icon(string|Stringable $icon): static
    {
        $this->icon = $icon;
        return $this;
    }
    
    /**
     * Sets a message as success.
     *
     * @return static $this
     */
    public function success(): static
    {
        $this->type = 'success';
        return $this;
    }
    
    /**
     * Sets message as warning.
     *
     * @return static $this
     */
    public function warning(): static
    {
        $this->type = 'warning';
        return $this;
    }
    
    /**
     * Sets message as danger.
     *
     * @return static $this
     */
    public function danger(): static
    {
        $this->type = 'danger';
        return $this;
    }
    
    /**
     * Sets message as info.
     *
     * @return static $this
     */
    public function info(): static
    {
        $this->type = 'info';
        return $this;
    }
    
    /**
     * Sets attributes.
     *
     * @param array $attributes
     * @return static $this
     */
    public function attributes(array $attributes): static
    {
        $this->attributes = $attributes;
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
     * Get content as a string of HTML.
     *
     * @return string
     */
    public function toHtml(): string
    {
        if ($this->html === '') {
            return '';
        }
        
        $html = '';
        
        if ($this->displayAsField) {
            $html .= '<div class="field field-crud"><div class="field-label"></div>';
            $html .= '<div class="field-body">';
        }
        
        if ($this->summary) {
            $open = $this->open ? ' open' : '';
            $html .= '<details'.$open.'>';
            $html .= '<summary class="link my-xs">';
            $html .= Str::esc($this->summary);
            $html .= '</summary>';
        }
        
        $attributes = new Attributes($this->attributes)
            ->add(name: 'class', value: 'crud-message');
        
        if ($this->type) {
            $attributes->add(name: 'class', value: 'alert');
            $attributes->add(name: 'class', value: $this->type);
        }
        
        if ($this->summary) {
            $attributes->add(name: 'class', value: 'mt-s');
        }
        
        $html .= '<div'.(string)$attributes.'>';
        
        // icon:
        $icon = $this->getIcon((string)$this->type);
        
        if ($icon) {
            $html .= '<div class="crud-message-icon">';
            $html .= $icon;
            $html .= '</div>';            
        }
        
        // body:
        $html .= '<div class="crud-message-body">';
        $html .= $this->html;
        $html .= '</div>';
        
        $html .= '</div>';
        
        if ($this->summary) {
            $html .= '</details>';
        }
        
        if ($this->displayAsField) {
            $html .= '</div>';
            $html .= '</div>';
        }
        
        return $html;
    }
    
    /**
     * Returns whether the HTML string is empty or not.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->html === '';
    }
    
    /**
     * Get the HTML string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toHtml();
    }
    
    /**
     * Returns the icon for the given type.
     *
     * @param string $type
     * @return string
     */
    protected function getIcon(string $type): string
    {
        if ($this->icon) {
            return Str::esc($this->icon);
        }
        
        return match ($type) {
            'success' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg>',
            
            'warning' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l6 0" /></svg>',
            
            'danger' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 8v4" /><path d="M12 16h.01" /></svg>',
            
            'info' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>',
            
            default => '',
        };
    }
}