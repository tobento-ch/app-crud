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
use Tobento\Service\Dater\DateFormatter;
use Tobento\Service\Support\HtmlString;
use Tobento\Service\Support\Str;

class Date
{
    /**
     * @var DateFormatter
     */
    protected DateFormatter $dateFormatter;
    
    /**
     * Create a new Date instance.
     *
     * @param null|string $format
     * @param null|DateFormatter $dateFormatter
     */
    public function __construct(
        protected null|string $format = null,
        null|DateFormatter $dateFormatter = null,
    ) {
        $this->dateFormatter = $dateFormatter ?: new DateFormatter();
    }
    
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
     * @param FieldInterface $field
     * @return string
     */
    protected function format(mixed $value, FieldInterface $field): string
    {
        if (!is_scalar($value)) {
            return '';
        }
        
        $value = (string)$value;
        
        if (empty($value)) {
            return '';
        }
        
        $format = $this->format;
        
        if (is_null($format)) {
            $format = $field instanceof Field\Text && $field->getType() === 'date'
                ? 'EE, dd. MMMM yyyy'
                : 'EE, dd. MMMM yyyy, HH:mm';
        }
        
        return Str::esc($this->dateFormatter->date(value: $value, format: $format));
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
                
        return $this->format(value: $value, field: $field);
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
        
        $html = '<span class="crud-values">';

        foreach($values as $value) {
            $value = $this->format(value: $value, field: $field);
            
            if ($value !== '') {
                $html .= '<span>';
                $html .= $value;
                $html .= '</span>';                
            }
        }

        $html .= '</span>';
        return $html;
    }
}