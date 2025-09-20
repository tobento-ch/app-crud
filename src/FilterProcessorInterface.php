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

use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Action\ActionInterface;

/**
 * FilterProcessorInterface
 */
interface FilterProcessorInterface
{
   /**
     * Process filters.
     *
     * @param FiltersInterface $filters
     * @param ActionInterface $action
     * @return FiltersInterface
     */
    public function processFilters(FiltersInterface $filters, ActionInterface $action): FiltersInterface;
}