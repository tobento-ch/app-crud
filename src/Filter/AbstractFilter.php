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

/**
 * AbstractFilter
 */
abstract class AbstractFilter implements FilterInterface
{
    /**
     * @var bool
     */
    protected bool $isActive = false;
    
    /**
     * @var string
     */
    protected string $group = 'header';
    
    /**
     * @var bool
     */
    protected bool $open = true;

    /**
     * @var string
     */
    protected string $view = 'crud/filter';
    
    /**
     * @var null|string
     */
    protected null|string $label = null;
    
    /**
     * @var null|string
     */
    protected null|string $description = null;
    
    /**
     * @var array<array-key, callable|Resolve>
     */
    protected array $beforeCallables = [];
    
    /**
     * @var array<array-key, callable|Resolve>
     */
    protected array $afterCallables = [];
    
    /**
     * @var null|array
     */
    protected null|array $whereParameters = null;
    
    /**
     * @var bool|callable
     */
    protected $displayIf = true;
    
    /**
     * Returns the name.
     *
     * @return string
     */
    abstract public function name(): string;
    
    /**
     * Returns the field name.
     *
     * @return string
     */
    public function fieldName(): string
    {
        return '';
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
        //
    }
    
    /**
     * Returns the applied parameters.
     *
     * @return array
     */
    public function getAppliedParameters(): array
    {
        return [];
    }
    
    /**
     * Returns the where parameters.
     *
     * @return array
     */
    public function getWhereParameters(): array
    {
        return $this->whereParameters ?: [];
    }
    
    /**
     * Sets the where parameters.
     *
     * @param array $where
     * @return static $this
     */
    public function setWhereParameters(array $where): static
    {
        $this->whereParameters = $where;
        return $this;
    }
    
    /**
     * Returns the order by parameters.
     *
     * @return array
     */
    public function getOrderByParameters(): array
    {
        return [];
    }
    
    /**
     * Returns the limit parameter.
     *
     * @return array
     */
    public function getLimitParameter(): array
    {
        return [];
    }
    
    /**
     * Returns the before callables to resolve.
     *
     * @return array<array-key, callable|Resolve>
     */
    public function getBeforeCallables(): array
    {
        return $this->beforeCallables;
    }
    
    /**
     * Returns the after callables to resolve.
     *
     * @return array<array-key, callable|Resolve>
     */
    public function getAfterCallables(): array
    {
        return $this->afterCallables;
    }
    
    /**
     * Returns if the filter is active, otherwise false.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }
    
    /**
     * Sets the group.
     *
     * @param string $group
     * @return static $this
     */
    public function group(string $group): static
    {
        $this->group = $group;
        return $this;
    }
    
    /**
     * Returns the group.
     *
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }
    
    /**
     * Sets if the filter is open.
     *
     * @param bool $open
     * @return static $this
     */
    public function open(bool $open = true): static
    {
        $this->open = $open;
        return $this;
    }
    
    /**
     * Returns if the filter is open.
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->open;
    }

    /**
     * Sets the view.
     *
     * @param string $name
     * @return static $this
     */
    public function view(string $name): static
    {
        $this->view = $name;
        return $this;
    }
    
    /**
     * Sets the label.
     *
     * @param string $text
     * @return static $this
     */
    public function label(string $text): static
    {
        $this->label = $text;
        return $this;
    }
    
    /**
     * Sets the description.
     *
     * @param string $text
     * @return static $this
     */
    public function description(string $text): static
    {
        $this->description = $text;
        return $this;
    }
    
    /**
     * Returns the rendered filter.
     *
     * @param ViewInterface $view
     * @return string
     */
    public function render(ViewInterface $view): string
    {
        return '';
    }
    
    /**
     * Add a before callable.
     *
     * @param callable|Resolve $callable
     * @return static $this
     */
    public function before(callable|Resolve $callable): static
    {
        $this->beforeCallables[] = $callable;
        return $this;
    }
    
    /**
     * Add an after callable.
     *
     * @param callable|Resolve $callable
     * @return static $this
     */
    public function after(callable|Resolve $callable): static
    {
        $this->afterCallables[] = $callable;
        return $this;
    }
    
    /**
     * Display the filter if the rule set validates to true.
     *
     * @param bool|callable $rule
     * @return static $this
     */
    public function displayIf(bool|callable $rule): static
    {
        $this->displayIf = $rule;
        return $this;
    }
    
    /**
     * Returns whether the filter is displayable.
     *
     * @return bool|callable
     */
    public function getDisplayIf(): bool|callable
    {
        return $this->displayIf;
    }
}