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

/**
 * ModalButton
 */
class ModalButton extends AbstractFilter
{
    /**
     * @var array
     */
    protected array $attributes = [];
    
    /**
     * @var bool
     */
    protected bool $hasFilters = false;
    
    /**
     * Create a new ModalButton.
     *
     * @param string $name
     */
    final public function __construct(
        protected string $name = 'modal-button',
    ) {
        $this->label('Filters');
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
     * Applies the data to filter.
     *
     * @param InputInterface $input Might come from user input. So be careful.
     * @param FiltersInterface $filters
     * @param ActionInterface $action
     * @return void
     */
    public function apply(InputInterface $input, FiltersInterface $filters, ActionInterface $action): void
    {
        if (!$filters->group('modal')->empty()) {
            $this->hasFilters = true;
        }
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
        if (!$this->hasFilters) {
            return '';
        }
        
        $attributes = new Attributes($this->attributes);
        $attributes->add('data-filter', $this->name());
        $attributes->add('data-modal-trigger', 'filters');
        
        if (! $attributes->has('class')) {
            $attributes->add('class', 'button text-xs');
        }
        
        $html = '<div'.$attributes.'>';
        $html .= $view->esc($this->label);
        $html .= '</div>';
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
}