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

use Stringable;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\Service\Support\HtmlString;
use Tobento\Service\Support\Str as SupportStr;

class Str
{
    /**
     * Create a new Str instance.
     *
     * @param null|int $trimWidth
     * @param string $trimMarker
     * @param string $delimiter
     * @param bool $arrayToJson
     * @param bool $pre
     */
    public function __construct(
        protected null|int $trimWidth = null,
        protected string $trimMarker = '...',
        protected string $delimiter = ', ',
        protected bool $arrayToJson = false,
        protected bool $pre = false,
    ) {}
    
    /**
     * Formatting value.
     *
     * @param mixed $value
     * @param FieldInterface $field
     * @return string|Stringable
     */
    public function __invoke(mixed $value, FieldInterface $field): string|Stringable
    {
        if (is_array($value)) {
            if ($this->arrayToJson) {
                $value = json_encode($value, JSON_PRETTY_PRINT);
                return $this->finalize($value);
            }

            return $this->finalize(
                $this->formatValues($value, $field)
            );
        }

        return $this->finalize(
            $this->formatValue($value, $field)
        );
    }

    /**
     * Formats value.
     *
     * @param mixed $value
     * @param FieldInterface $field
     * @return string|Stringable
     * @psalm-suppress TypeDoesNotContainType
     * @psalm-suppress RedundantCondition
     */
    protected function formatValue(mixed $value, Field\FieldInterface $field): string|Stringable
    {
        if ($value instanceof Stringable) {
            $value = (string)$value;
        }
        
        if (!is_scalar($value)) {
            return '';
        }

        $options = $field instanceof Field\OptionsAwareInterface
            ? $field->getOptions()
            : [];

        $value = is_int($value) || is_string($value)
            ? ($options[$value] ?? $value)
            : $value;

        return is_scalar($value) ? (string)$value : '';
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

        $options = $field instanceof Field\OptionsAwareInterface
            ? $field->getOptions()
            : [];

        $values = array_map(function(mixed $value) use ($options): mixed {
            if (is_int($value) || is_string($value)) {
                return $options[$value] ?? $value;
            }

            return $value;
        }, $values);

        return implode($this->delimiter, $values);
    }
    
    /**
     * Finalizes the formatted value by applying trimming and optional
     * <pre> wrapping.
     *
     * @param string $value
     * @return string|HtmlString
     */
    protected function finalize(string $value): string|HtmlString
    {
        // Trim if needed
        if (!is_null($this->trimWidth)) {
            $value = mb_strimwidth($value, 0, $this->trimWidth, $this->trimMarker);
        }

        // Wrap in <pre> if enabled
        if ($this->pre) {
            return new HtmlString('<pre>'.SupportStr::esc($value).'</pre>');
        }

        return $value;
    }
}