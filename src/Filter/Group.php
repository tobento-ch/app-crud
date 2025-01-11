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
 * Group
 */
class Group extends AbstractFilter
{
    /**
     * @var null|FiltersInterface $filters
     */
    protected null|FiltersInterface $filters = null;
    
    /**
     * @var string
     */
    protected string $view = 'crud/filter/group';
    
    /**
     * Create a new Group.
     *
     * @param string $name
     */
    final public function __construct(
        private string $name,
    ) {}
    
    /**
     * Create a new instance.
     *
     * @param string $name
     * @return static
     */
    public static function new(string $name): static
    {
        return new static($name);
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
        $this->filters = $filters->group($this->name());
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
        if ($this->getFilters()->empty()) {
            return '';
        }
        
        return $view->render(
            view: $this->view,
            data: [
                'name' => $this->name(),
                'label' => $this->label ?: $this->name(),
                'labelFor' => '',
                'body' => '',
                'description' => $this->description,
                'open' => $this->isOpen(),             
                'filter' => $this,
                'filters' => $this->getFilters(),
            ],
        );
    }
    
    /**
     * Returns the grouped filters.
     *
     * @return FiltersInterface
     */
    public function getFilters(): FiltersInterface
    {
        if (is_null($this->filters)) {
            return new Filters();
        }
        
        return $this->filters;
    }
}