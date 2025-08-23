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

class Formatters
{
    /**
     * @var array<array-key, callable>
     */
    protected array $formatters = [];
    
    /**
     * Create a new Formatters instance.
     *
     * @param callable ...$formatter
     */
    public function __construct(
        ...$formatter,
    ) {
        $this->formatters = $formatter;
    }
    
    /**
     * Formatting value.
     *
     * @param mixed $value
     * @param FieldInterface $field
     * @return mixed
     */
    public function __invoke(mixed $value, FieldInterface $field): mixed
    {
        if (empty($this->formatters)) {
            return $value;
        }
        
        foreach($this->formatters as $formatter) {
            $value = Str::esc($formatter($value, $field));
        }
        
        return new HtmlString($value);
    }
}