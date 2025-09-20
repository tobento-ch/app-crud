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

use JsonException;
use Psr\Container\ContainerInterface;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Filter\Resolve;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\Service\Autowire\Autowire;
use Tobento\Service\Collection\Arr;
use Tobento\Service\Cookie\CookiesInterface;
use Tobento\Service\Cookie\CookieValuesInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Session\SessionInterface;
use Throwable;

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
     * @param RequesterInterface $requester
     * @param string $storage cookie or seesion
     */
    public function __construct(
        ContainerInterface $container,
        protected RequesterInterface $requester,
        protected string $storage = 'cookie',
    ) {
        $this->autowire = new Autowire($container);
    }
    
    /**
     * Process filters.
     *
     * @param FiltersInterface $filters
     * @param ActionInterface $action
     * @return FiltersInterface
     */
    public function processFilters(FiltersInterface $filters, ActionInterface $action): FiltersInterface
    {
        $input = $this->requester->input();
        
        if ($input->has('filter')) {
            // we combine session filter data with input data
            // so that indiviual filter forms can be sumbitted
            // without losing previously filtered values.
            $storageData = $this->fetchData(action: $action);
            
            $data = $input->get('filter', []);
            $data = array_replace_recursive($storageData, $data);
        } else {
            $data = $this->fetchData(action: $action);
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
        
        // filter displayable only:
        $filters = $filters->filter(function(FilterInterface $filter) use ($filters, $action, $data): bool {
            
            if (is_bool($filter->getDisplayIf())) {
                return (bool)$filter->getDisplayIf();
            }
            
            return $this->autowire->call(
                $filter->getDisplayIf(),
                ['filter' => $filter, 'input' => new Input($data), 'action' => $action, 'filters' => $filters],
            );
        });
        
        if ($input->has('filter') || $input->has('clear-filter')) {
            $data = $filters->getAppliedParameters();
            $this->storeData(action: $action, data: $data);
        }
        
        return $filters;
    }
    
    /**
     * Fetches data from storage.
     *
     * @param ActionInterface $action
     * @return array
     */
    protected function fetchData(ActionInterface $action): array
    {
        $name = $action->controller()->resourceName().'-'.$action->name();
        
        if ($this->storage === 'session') {
            $session = $this->autowire->container()->get(SessionInterface::class);
            return $session->get('crud-filters-'.$name, []);
        }
        
        if ($this->storage === 'cookie') {
            $cookieValues = $this->requester->request()->getAttribute(CookieValuesInterface::class);

            try {
                return json_decode($cookieValues->get('crud-filters-'.$name), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException|Throwable $e) {
                return [];
            }
        }
        
        return [];
    }
    
    /**
     * Stores data into storage.
     *
     * @param ActionInterface $action
     * @param array $data
     * @return void
     */
    protected function storeData(ActionInterface $action, array $data): void
    {
        $name = $action->controller()->resourceName().'-'.$action->name();
        
        if ($this->storage === 'session') {
            $session = $this->autowire->container()->get(SessionInterface::class);
            $session->set('crud-filters-'.$name, $data);
            return;
        }
        
        if ($this->storage === 'cookie') {
            $cookies = $this->requester->request()->getAttribute(CookiesInterface::class);
            
            try {
                $cookies->add(
                    name: 'crud-filters-'.$name,
                    value: json_encode($data, JSON_THROW_ON_ERROR),
                );
            } catch (JsonException|Throwable $e) {
                //
            }
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