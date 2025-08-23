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

namespace Tobento\App\Crud\Field\Formatter;

use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\Service\Support\HtmlString;
use Tobento\Service\Support\Str;

class CssClass
{
    /**
     * Create a new CssClass instance.
     *
     * @param string $class
     */
    public function __construct(
        protected string $class,
    ) {}
    
    /**
     * Formatting value.
     *
     * @param mixed $value
     * @param FieldInterface $field
     * @return string|HtmlString
     */
    public function __invoke(mixed $value, FieldInterface $field): string|HtmlString
    {
        if (is_array($value)) {
            $formatted = $this->formatValues(values: $value, field: $field);
        } else {
            $formatted = $this->formatValue(value: $value, field: $field);
        }
        
        if ($formatted === '') {
            return '';
        }
        
        return new HtmlString($formatted);
    }

    /**
     * Formats value.
     *
     * @param mixed $value
     * @param null|string $label
     * @return string
     */
    protected function format(mixed $value, null|string $label = null): string
    {
        if (!empty($label)) {
            return '<span class="'.Str::esc($this->class).'">'.Str::esc($label).'</span>';
        }
        
        if (!is_scalar($value)) {
            return '';
        }
        
        $value = (string)$value;
        
        if ($value === '') {
            return '';
        }
        
        if ($this->class === '') {
            return '<span>'.Str::esc($value).'</span>';
        }
        
        return '<span class="'.Str::esc($this->class).'">'.Str::esc($value).'</span>';
    }

    /**
     * Formats value.
     *
     * @param array $values
     * @param FieldInterface $field
     * @return string
     */
    protected function formatValue(mixed $value, Field\FieldInterface $field): string
    {
        if (!is_scalar($value)) {
            $value = '';
        }
        
        $options = [];
        
        if ($field instanceof Field\OptionsAwareInterface) {
            $options = $field->getOptions();
        }
        
        $label = is_int($value) || is_string($value) ? $options[$value] ?? null : null;
        
        return $this->format(value: $value, label: $label);
    }
    
    /**
     * Formats values.
     *
     * @param array $values
     * @param FieldInterface $field
     * @return string
     */
    protected function formatValues(array $values, Field\FieldInterface $field): string
    {
        if (empty($values)) {
            return '';
        }
        
        $options = [];
        
        if ($field instanceof Field\OptionsAwareInterface) {
            $options = $field->getOptions();
        }
        
        $html = '<span class="crud-values">';

        foreach($values as $value) {
            if (is_int($value) || is_string($value)) {
                $html .= $this->format(value: $value, label: $options[$value] ?? null);
                continue;
            }

            $html .= $this->format(value: $value);
        }

        $html .= '</span>';
        return $html;
    }
}