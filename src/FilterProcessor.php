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

namespace Tobento\App\Crud;

use Psr\Container\ContainerInterface;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Filter\Resolve;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\Service\Autowire\Autowire;
use Tobento\Service\Collection\Arr;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Session\SessionInterface;

/**
 * FilterProcessor
 */
class FilterProcessor implements FilterProcessorInterface
{
    /**
     * @var Autowire
     */
    protected Autowire $autowire;
    
    /**
     * Create a new FilterProcessor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(
        ContainerInterface $container,
        protected RequesterInterface $requester,
        protected SessionInterface $session,
    ) {
        $this->autowire = new Autowire($container);
    }
    
    /**
     * Process filters.
     *
     * @param FiltersInterface $filters
     * @param ActionInterface $action
     * @return void
     */
    public function processFilters(FiltersInterface $filters, ActionInterface $action): void
    {
        $input = $this->requester->input();
        
        //if ($this->requester->method() === 'POST') {
        if ($input->has('filter')) {
            // we combine session filter data with input data
            // so that indiviual filter forms can be sumbitted
            // without losing previously filtered values.
            $name = $action->controller()->resourceName();
            $sessionData = $this->session->get('crudFilters.'.$name, []);
            
            $data = $input->get('filter', []);
            $data = array_merge($sessionData, $data);
        } else {
            $name = $action->controller()->resourceName();
            $data = $this->session->get('crudFilters.'.$name, []);
        }
        
        // clear filter data:
        if ($input->has('clear-filter')) {
            $filtersToClear = $input->get('clear-filter');
            if (is_array($filtersToClear)) {
                $data = Arr::except($data, $filtersToClear);
            } else {
                $data = [];
            }
        }
        
        // apply filters:
        foreach($filters as $filter) {
            $this->callCallables(
                callables: $filter->getBeforeCallables(),
                filters: $filters,
                filter: $filter,
                action: $action,
                data: $data
            );
            
            $filter->apply(new Input($data), $filters, $action);
        }
        
        // call after callables:
        foreach($filters as $filter) {
            $this->callCallables(
                callables: $filter->getAfterCallables(),
                filters: $filters,
                filter: $filter,
                action: $action,
                data: $data
            );
        }
        
        if ($input->has('filter') || $input->has('clear-filter')) {
            $name = $action->controller()->resourceName();
            $data = $filters->getAppliedParameters();
            $this->session->set('crudFilters.'.$name, $data);
        }
    }
    
    /**
     * Calls the given callables.
     *
     * @param array<array-key, callable|Resolve> $callables
     * @param FiltersInterface $filters
     * @param FilterInterface $filter
     * @param ActionInterface $action
     * @param array $data
     * @return void
     */
    protected function callCallables(
        array $callables,
        FiltersInterface $filters,
        FilterInterface $filter,
        ActionInterface $action,
        array $data,
    ): void {
        foreach($callables as $callable) {
            $params = ['filter' => $filter, 'input' => new Input($data), 'action' => $action, 'filters' => $filters];
            
            if ($callable instanceof Resolve) {
                $callable->resolved($this->autowire->call($callable->callable(), $params));
            } else {
                $this->autowire->call($callable, $params);
            }
        }
    }
}