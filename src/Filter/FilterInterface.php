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
 * FilterInterface
 */
interface FilterInterface
{
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string;
    
    /**
     * Returns the field name.
     *
     * @return string
     */
    public function fieldName(): string;
    
    /**
     * Applies the data to filter.
     *
     * @param InputInterface $input Might come from user input. So be careful.
     * @param FiltersInterface $filters
     * @param ActionInterface $action
     * @return void
     */
    public function apply(InputInterface $input, FiltersInterface $filters, ActionInterface $action): void;
    
    /**
     * Returns the applied parameters.
     *
     * @return array
     */
    public function getAppliedParameters(): array;
    
    /**
     * Returns the where parameters.
     *
     * @return array
     */
    public function getWhereParameters(): array;
    
    /**
     * Returns the order by parameters.
     *
     * @return array
     */
    public function getOrderByParameters(): array;
    
    /**
     * Returns the limit parameter.
     *
     * @return array
     */
    public function getLimitParameter(): array;
    
    /**
     * Returns the before callables to resolve.
     *
     * @return array<array-key, callable|Resolve>
     */
    public function getBeforeCallables(): array;
    
    /**
     * Returns the after callables to resolve.
     *
     * @return array<array-key, callable|Resolve>
     */
    public function getAfterCallables(): array;
    
    /**
     * Returns if the filter is active, otherwise false.
     *
     * @return bool
     */
    public function isActive(): bool;
    
    /**
     * Sets the group.
     *
     * @param string $group
     * @return static $this
     */
    public function group(string $group): static;
    
    /**
     * Returns the group.
     *
     * @return string
     */
    public function getGroup(): string;
    
    /**
     * Sets if the filter is open.
     *
     * @param bool $open
     * @return static $this
     */
    public function open(bool $open = true): static;
    
    /**
     * Returns if the filter is open.
     *
     * @return bool
     */
    public function isOpen(): bool;

    /**
     * Sets the view.
     *
     * @param string $name
     * @return static $this
     */
    public function view(string $name): static;
    
    /**
     * Sets the label.
     *
     * @param string $text
     * @return static $this
     */
    public function label(string $text): static;
    
    /**
     * Sets the description.
     *
     * @param string $text
     * @return static $this
     */
    public function description(string $text): static;
    
    /**
     * Returns the rendered filter.
     *
     * @param ViewInterface $view
     * @return string
     */
    public function render(ViewInterface $view): string;
}