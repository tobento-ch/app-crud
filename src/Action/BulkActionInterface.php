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

namespace Tobento\App\Crud\Action;

use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\Service\View\ViewInterface;

/**
 * BulkActionInterface
 */
interface BulkActionInterface
{
    /**
     * Sets the action processor.
     *
     * @param ActionProcessorInterface $actionProcessor
     * @return static $this
     */
    public function setActionProcessor(ActionProcessorInterface $actionProcessor): static;
    
    /**
     * Returns the process bulk action.
     *
     * @return callable
     */
    public function getBulkProcessAction(): callable;
    
    /**
     * Returns the html of action. MUST be escaped.
     *
     * @param ViewInterface $view
     * @return string
     */
    public function render(ViewInterface $view): string;
}