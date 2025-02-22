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
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\View\ViewInterface;

/**
 * PaginationItemsPerPage
 */
class PaginationItemsPerPage extends AbstractFilter
{
    /**
     * @var null|Filter\Pagination
     */
    protected null|Filter\Pagination $paginationFilter = null;
    
    /**
     * Create a new PaginationItemsPerPage.
     *
     * @param int $show The default items to show per page.
     */
    final public function __construct(
        protected int $show = 100,
    ) {}
    
    /**
     * Create a new instance.
     *
     * @param int $show
     * @return static
     */
    public static function new(int $show = 100): static
    {
        return new static($show);
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'pagination_items_'.$this->getGroup();
    }
    
    /**
     * Sets the group.
     *
     * @param string $group
     * @return static $this
     */
    public function group(string $group): static
    {
        $this->group = preg_replace('/[^a-z-]/', '_', $group);
        return $this;
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
        $this->paginationFilter = $filters->byClass(Filter\Pagination::class)->first();
        $name = 'pagination';
        $limit = 100;
        
        if ($this->paginationFilter) {
            $name = $this->paginationFilter->filterName();
            $limit = $this->paginationFilter->maxItemsPerPage();
        }
        
        $show = $input->get($name.'.show');
        $show = is_array($show) ? $show[0] ?? $this->show : $show;
        $this->show = is_scalar($show) ? (int)$show : $this->show;
        
        if ($this->show > $limit) {
            $this->show = $limit;
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
        if (is_null($this->paginationFilter)) {
            return '';
        }
        
        $form = $view->form();
        $idAttribute = $form->nameToId('filter.pagination.show.'.$this->getGroup());
        $attributes = [
            'id' => $idAttribute,
            'min' => '1',
            'max' => (string)$this->paginationFilter->maxItemsPerPage(),
        ];
        
        if (is_null($this->label)) {
            $this->label = $view->trans('Per page');
        }
        
        if (empty($this->label)) {
            $attributes['aria-label'] = $this->name();
        }
        
        $body = $form->input(
            name: $form->nameToArray('filter.pagination.show'),
            type: 'number',
            value: (string)$this->show,
            attributes: $attributes,
        );
        
        return $view->render(
            view: $this->view,
            data: [
                'name' => $this->name(),
                'label' => $this->label,
                'labelFor' => $this->label ? $idAttribute : '',
                'body' => $body, // must be escaped!
                'description' => $this->description,
                'open' => $this->isOpen(),
                'filter' => $this,
            ],
        );
    }
}