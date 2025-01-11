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

/**
 * HasActionProcessor
 */
trait HasActionProcessor
{
    /**
     * @var null|ActionProcessorInterface
     */
    protected null|ActionProcessorInterface $actionProcessor = null;
    
    /**
     * Sets the action processor.
     *
     * @param ActionProcessorInterface $actionProcessor
     * @return static $this
     */
    public function setActionProcessor(ActionProcessorInterface $actionProcessor): static
    {
        $this->actionProcessor = $actionProcessor;
        return $this;
    }
    
    /**
     * Returns the action processor.
     *
     * @return ActionProcessorInterface
     */
    protected function actionProcessor(): ActionProcessorInterface
    {
        if (is_null($this->actionProcessor)) {
            throw new \LogicException('Action processor is not accessible yet.');
        }
        
        return $this->actionProcessor;
    }
}