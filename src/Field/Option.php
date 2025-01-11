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

namespace Tobento\App\Crud\Field;

use Tobento\Service\Support\Str;

/**
 * Option
 */
class Option
{
    /**
     * @var string
     */
    protected string $html = '';
    
    /**
     * Create a new Option.
     *
     * @param string $value
     * @param null|string $text
     */
    public function __construct(
        protected string $value,
        null|string $text = null,
    ) {
        if ($text) {
            $this->text($text);
        }
    }

    /**
     * Returns the value.
     *
     * @return string
     */
    public function value(): string
    {
        return $this->value;
    }
    
    /**
     * Adds a text.
     *
     * @param string $text
     * @return static $this
     */
    public function text(string $text): static
    {
        $this->html .= '<span>'.Str::esc($text).'</span>';
        return $this;
    }

    /**
     * Adds html. Must be escaped.
     *
     * @param string $html
     * @return static $this
     */
    public function html(string $html): static
    {
        $this->html .= '<span>'.$html.'</span>';
        return $this;
    }
    
    /**
     * Returns the html.
     *
     * @return string
     */
    public function getHtml(): string
    {
        return $this->html;
    }
}