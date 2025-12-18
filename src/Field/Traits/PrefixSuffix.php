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

namespace Tobento\App\Crud\Field\Traits;

use Stringable;
use Tobento\App\Crud\Field\FieldInterface;

trait PrefixSuffix
{
    /**
     * @var null|string|Stringable
     */
    protected null|string|Stringable $prefix = null;
    
    /**
     * @var null|string|Stringable
     */
    protected null|string|Stringable $suffix = null;
    
    /**
     * @var array
     */
    protected array $prefixAttributes = [];
    
    /**
     * @var array
     */
    protected array $suffixAttributes = [];
    
    /**
     * Sets a prefix.
     *
     * @param string|Stringable|callable $text
     * @param array $attributes
     * @param null|string $action
     * @return static $this
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function prefix(string|Stringable|callable $text, array $attributes = [], null|string $action = null): static
    {
        $this->prefixAttributes = $attributes;
        
        if (is_null($action) && !is_callable($text)) {
            $this->prefix = $text;
            return $this;
        }
        
        if (!is_callable($text)) {
            $this->resolve(static function (FieldInterface $field) use ($text) {
                $field->prefix(text: $text, action: null);
            }, action: $action);
            
            return $this;
        }
        
        $this->resolve(
            resolve: $text,
            resolved: static function(FieldInterface $field, mixed $resolved): void {
                if (is_bool($resolved)) {
                    $field->prefix(text: $resolved);
                }
            },
        );
        
        return $this;
    }
    
    /**
     * Returns the prefix.
     *
     * @return null|string|Stringable
     */
    public function getPrefix(): null|string|Stringable
    {
        return $this->prefix;
    }
    
    /**
     * Returns the prefix attributes.
     *
     * @return array
     */
    public function getPrefixAttributes(): array
    {
        return $this->prefixAttributes;
    }
    
    /**
     * Sets a suffix.
     *
     * @param string|Stringable|callable $text
     * @param array $attributes
     * @param null|string $action
     * @return static $this
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function suffix(string|Stringable|callable $text, array $attributes = [], null|string $action = null): static
    {
        $this->suffixAttributes = $attributes;
        
        if (is_null($action) && !is_callable($text)) {
            $this->suffix = $text;
            return $this;
        }
        
        if (!is_callable($text)) {
            $this->resolve(static function (FieldInterface $field) use ($text) {
                $field->suffix(text: $text, action: null);
            }, action: $action);
            
            return $this;
        }
        
        $this->resolve(
            resolve: $text,
            resolved: static function(FieldInterface $field, mixed $resolved): void {
                if (is_bool($resolved)) {
                    $field->suffix($resolved);
                }
            },
        );
        
        return $this;
    }
    
    /**
     * Returns the suffix.
     *
     * @return null|string|Stringable
     */
    public function getSuffix(): null|string|Stringable
    {
        return $this->suffix;
    }
    
    /**
     * Returns the suffix attributes.
     *
     * @return array
     */
    public function getSuffixAttributes(): array
    {
        return $this->suffixAttributes;
    }
}