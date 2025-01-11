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
use Tobento\Service\Pagination\Pagination as ServicePagination;
use Tobento\Service\Pagination\PaginationInterface;

/**
 * Pagination
 */
class Pagination extends AbstractFilter
{
    /**
     * @var null|PaginationInterface
     */
    protected null|PaginationInterface $pagination = null;
    
    /**
     * @var int
     */
    protected int $show = 100;
    
    /**
     * @var int
     */
    protected int $page = 1;
    
    /**
     * @var string
     */
    protected string $group = 'header';
    
    /**
     * @var null|int
     */
    protected static null|int $totalItems = null;
    
    /**
     * @var string
     */
    protected string $view = 'crud/filter/pagination';

    /**
     * Create a new Pagination.
     *
     * @param int $show The default items to show per page.
     * @param int $maxItemsPerPage The max. items (limit) to show per page.
     */
    final public function __construct(
        int $show = 100,
        protected int $maxItemsPerPage = 1000,
    ) {
        $this->show = $show;
    }
    
    /**
     * Create a new instance.
     *
     * @param int $show
     * @param int $maxItemsPerPage
     * @return static
     */
    public static function new(int $show = 100, int $maxItemsPerPage = 1000): static
    {
        return new static($show, $maxItemsPerPage);
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'pagination_'.$this->getGroup();
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
        $show = $input->get($this->filterName().'.show');
        $show = is_array($show) ? $show[0] ?? $this->show : $show;
        $this->show = is_scalar($show) ? (int)$show : $this->show;
        
        $page = $input->get($this->filterName().'.page');
        $page = is_array($page) ? $page[0] ?? 1 : $page;
        $this->page = is_scalar($show) ? (int)$page : 1;
        
        // cache total items count if multiple pagination filters: header and footer position e.g.
        if (is_null(static::$totalItems)) {
            $repository = $action->controller()->repository();
            $totalItems = $repository->count(where: $filters->getWhereParameters());
            static::$totalItems = $totalItems;
        } else {
            $totalItems = static::$totalItems;
        }

        $pagination = new ServicePagination(
            totalItems: $totalItems,
            currentPage: $this->page,
            itemsPerPage: $this->show,
            maxItemsPerPage: $this->maxItemsPerPage,
            maxPagesToShow: 10000000000,
        );
        
        $this->show = $pagination->getItemsPerPage(); // for limit
        
        // handle if current page does not exist:
        if (! $pagination->hasCurrentPage()) {
            $pagination = $pagination->withCurrentPage(1);
            $this->page = 1;
        }
        
        $this->pagination = $pagination;
        $this->isActive = true;
    }
    
    /**
     * Returns the applied parameters.
     *
     * @return array
     */
    public function getAppliedParameters(): array
    {
        return [
            $this->filterName() => ['show' => $this->show, 'page' => $this->page],
        ];
    }
    
    /**
     * Returns the limit parameter.
     *
     * @return array
     */
    public function getLimitParameter(): array
    {
        $pagination = $this->pagination();
        
        return [$pagination->getItemsPerPage(), $pagination->getItemsOffset()];
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
        $pagination = $this->pagination();
        $form = $view->form();
        $idAttribute = $form->nameToId('filter.'.$this->filterName().'.page.'.$this->getGroup());
        $body = $form->input(
            name: $form->nameToArray('filter.'.$this->filterName().'.page'),
            type: 'number',
            value: (string)$pagination->getCurrentPage(),
            attributes: ['id' => $idAttribute, 'min' => '1', 'max' => (string) max(1, $pagination->getTotalPages())],
            selected: null,
            withInput: true,
        );
        
        if (is_null($this->description)) {
            $this->description = $view->trans(
                message: 'of :num Pages | Showing :from - :to from :total records',
                parameters: [
                    ':num' => max(1, $pagination->getTotalPages()),
                    ':from' => $pagination->getTotalItemsFrom(),
                    ':to' => $pagination->getTotalItemsTo(),
                    ':total' => $pagination->getTotalItems(),
                ],
            );
        }
        
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
    
    /**
     * Returns the filter name.
     *
     * @return string
     */
    public function filterName(): string
    {
        return 'pagination';
    }
    
    /**
     * Returns the max item per page.
     *
     * @return int
     */
    public function maxItemsPerPage(): int
    {
        return $this->maxItemsPerPage;
    }
    
    /**
     * Clears the total items count.
     *
     * @return static $this
     */
    public function clearTotalItemsCount(): static
    {
        static::$totalItems = null;
        return $this;
    }
    
    /**
     * Returns the pagination.
     *
     * @return PaginationInterface
     */
    public function pagination(): PaginationInterface
    {
        if ($this->pagination) {
            return $this->pagination;
        }
        
        return $this->pagination = new ServicePagination(
            totalItems: 1,
            currentPage: 1,
            itemsPerPage: 1,
            maxItemsPerPage: 8,
        );
    }
}