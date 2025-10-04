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

class Badge
{
    /**
     * Create a new Badge instance.
     *
     * @param array<array-key, string> $classes
     * @param string $fallbackClass
     * @param null|int $limit
     */
    public function __construct(
        protected array $classes = ['0' => 'text-error', '1' => 'text-success'],
        protected string $fallbackClass = 'text-black',
        protected null|int $limit = null,
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
     * To badge.
     *
     * @param mixed $value
     * @param null|string $label
     * @return string
     */
    protected function toBadge(mixed $value, null|string $label = null): string
    {
        if (is_int($value) || is_string($value)) {
            $class = $this->classes[$value] ?? $this->fallbackClass;
        } else {
            $class = $this->fallbackClass;
        }
        
        if (!empty($label)) {
            return '<span class="crud-badge '.Str::esc($class).'">'.Str::esc($label).'</span>';
        }
        
        if (!is_scalar($value)) {
            return '';
        }
        
        $value = (string)$value;
        
        if ($value === '') {
            return '';
        }
        
        return '<span class="crud-badge '.Str::esc($class).'">'.Str::esc($value).'</span>';
    }

    /**
     * Formats value.
     *
     * @param array $value
     * @param FieldInterface $field
     * @return string
     * @psalm-suppress TypeDoesNotContainType
     * @psalm-suppress RedundantCondition
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
        
        return $this->toBadge(value: $value, label: $label);
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
        
        $html = '<span class="crud-badges">';
        
        $sliced = array_slice($values, 0, $this->limit);
        
        foreach($sliced as $value) {
            if (is_int($value) || is_string($value)) {
                $html .= $this->toBadge(value: $value, label: $options[$value] ?? null);
                continue;
            }

            $html .= $this->toBadge(value: $value);
        }
        
        if (count($sliced) < count($values)) {
            $html .= '<span>...</span>';
        }

        $html .= '</span>';
        return $html;
    }
}