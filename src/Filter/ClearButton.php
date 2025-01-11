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

namespace Tobento\App\Crud\Filter;

use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\View\ViewInterface;
use Tobento\Service\Tag\Attributes;
use Tobento\Service\Uri\UriQuery;

/**
 * ClearButton
 */
class ClearButton extends AbstractFilter
{
    /**
     * @var array
     */
    protected array $attributes = [];
    
    /**
     * Create a new ClearButton.
     *
     * @param array<array-key, string> $filters The filter to clear. Empty all.
     * @param string $name
     */
    final public function __construct(
        protected array $filters,
        protected string $name,
    ) {
        $this->label('Clear Filters');
    }
    
    /**
     * Create a new instance.
     *
     * @param array<array-key, string> $filters The filter to clear. Empty all.
     * @param string $name
     * @return static
     */
    public static function new(array $filters = [], string $name = 'clear'): static
    {
        return new static($filters, $name);
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns the rendered filter.
     *
     * @param ViewInterface $view
     * @return string
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function render(ViewInterface $view): string
    {
        $href = '?clear-filter=1'; // clears all
        
        if (!empty($this->filters)) {
            $href = '?'.(new UriQuery(['clear-filter' => $this->filters]))->get();
        }
        
        $attributes = new Attributes($this->attributes);
        
        if (! $attributes->has('class')) {
            $attributes->add('class', 'button text-xs');
        }
        
        $attributes->add('href', $href);
        $attributes->add('data-filter', $this->name());
        
        $html = '<a'.$attributes.'>';
        $html .= $view->esc($this->label);
        $html .= '</a>';
        return $html;
    }
    
    /**
     * Sets the attributes.
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
     * Returns the attributes.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    
    /**
     * Returns the filters.
     *
     * @return array<array-key, string>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}