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

class Str
{
    /**
     * Create a new Str instance.
     *
     * @param null|int $trimWidth
     * @param string $trimMarker
     * @param string $delimiter
     * @param bool $arrayToJson
     */
    public function __construct(
        protected null|int $trimWidth = null,
        protected string $trimMarker = '...',
        protected string $delimiter = ', ',
        protected bool $arrayToJson = false,
    ) {}
    
    /**
     * Formatting value.
     *
     * @param mixed $value
     * @param FieldInterface $field
     * @return string
     */
    public function __invoke(mixed $value, FieldInterface $field): string
    {
        if (is_array($value)) {
            
            if ($this->arrayToJson) {
                return $this->formatValue(value: json_encode($value), field: $field);
            }
            
            return $this->formatValues(values: $value, field: $field);
        }
        
        return $this->formatValue(value: $value, field: $field);
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
        
        $value = is_int($value) || is_string($value) ? $options[$value] ?? $value : $value;
        
        if (!is_scalar($value)) {
            return '';
        }
        
        $value = (string)$value;
        
        if (is_null($this->trimWidth)) {
            return $value;
        }
        
        return mb_strimwidth($value, 0, $this->trimWidth, $this->trimMarker);
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
        
        $values = array_map(function(mixed $value) use ($options): mixed {
            if (is_int($value) || is_string($value)) {
                return $options[$value] ?? $value;
            }

            return $value;
        }, $values);

        $values = implode($this->delimiter, $values);
        
        if (is_null($this->trimWidth)) {
            return $values;
        }
        
        return mb_strimwidth($values, 0, $this->trimWidth, $this->trimMarker);
    }
}