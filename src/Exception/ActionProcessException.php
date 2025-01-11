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

namespace Tobento\App\Crud\Exception;

use Tobento\App\Crud\Action\ActionInterface;
use RuntimeException;
use Throwable;

/**
 * ActionProcessException
 */
class ActionProcessException extends RuntimeException
{
    /**
     * Create a new ActionProcessException.
     *
     * @param ActionInterface $action
     * @param string $message The message
     * @param int $code
     * @param null|Throwable $previous
     */
    public function __construct(
        protected ActionInterface $action,
        string $message = '',
        int $code = 0,
        null|Throwable $previous = null
    ) {
        if ($message === '') {            
            $message = sprintf('Processing %s action failed.', $action->name());
        }
        
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Returns the action.
     *
     * @return ActionInterface
     */
    public function action(): ActionInterface
    {
        return $this->action;
    }
}