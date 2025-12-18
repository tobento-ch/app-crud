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

class InputWrap implements Htmlable, Stringable
{
    /**
     * @var string
     */
    protected string $html = '';
    
    /**
     * Create a new instance.
     *
     * @param string|Stringable $input
     * @param null|string|Stringable $prefix
     * @param null|string|Stringable $suffix
     * @param array $prefixAttributes
     * @param array $suffixAttributes
     */
    public function __construct(
        protected string|Stringable $input,
        protected null|string|Stringable $prefix = '',
        protected null|string|Stringable $suffix = '',
        protected array $prefixAttributes = [],
        protected array $suffixAttributes = [],
    ) {}

    /**
     * Get content as a string of HTML.
     *
     * @return string
     */
    public function toHtml(): string
    {
        $prefix = Str::esc($this->prefix);
        $suffix = Str::esc($this->suffix);
        
        if ($prefix === '' && $suffix === '') {
            return Str::esc($this->input);
        }
                
        $html = '<div class="input-wrap">';
        
        if ($prefix !== '') {
            if ($this->prefix instanceof Htmlable) {
                $html .= $prefix;
            } else {
                $prefixAttributes = new Attributes($this->prefixAttributes)
                    ->add(name: 'class', value: 'prefix');

                $html .= '<div'.(string)$prefixAttributes.'>';
                $html .= $prefix;
                $html .= '</div>';
            }
        }
        
        $html .= Str::esc($this->input);
        
        if ($suffix !== '') {
            if ($this->suffix instanceof Htmlable) {
                $html .= $suffix;
            } else {
                $suffixAttributes = new Attributes($this->suffixAttributes)
                    ->add(name: 'class', value: 'suffix');

                $html .= '<div'.(string)$suffixAttributes.'>';
                $html .= $suffix;
                $html .= '</div>';
            }
        }
        
        $html .= '</div>';
        
        return $html;
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
}