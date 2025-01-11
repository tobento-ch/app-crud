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
 * ActionNotFoundException
 */
class ActionNotFoundException extends RuntimeException
{
    /**
     * Create a new ActionNotFoundException.
     *
     * @param string $actionName
     * @param string $message The message
     * @param int $code
     * @param null|Throwable $previous
     */
    public function __construct(
        protected string $actionName,
        string $message = '',
        int $code = 0,
        null|Throwable $previous = null
    ) {
        if ($message === '') {            
            $message = sprintf('Action %s not found.', $actionName);
        }
        
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Returns the action name.
     *
     * @return string
     */
    public function actionName(): string
    {
        return $this->actionName;
    }
}