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
use Tobento\App\Crud\Action\Delete;
use RuntimeException;
use Throwable;

/**
 * EntityUndeletableException
 */
class EntityUndeletableException extends RuntimeException
{
    /**
     * Create a new EntityUndeletableException.
     *
     * @param int|string $id
     * @param ActionInterface $action
     * @param string $message The message
     * @param int $code
     * @param null|Throwable $previous
     */
    public function __construct(
        protected int|string $id,
        protected null|ActionInterface $action = null,
        string $message = '',
        int $code = 0,
        null|Throwable $previous = null
    ) {
        if ($message === '' && $action instanceof Delete) {
            $message = $action->undeletableReason();
        }
        
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the id.
     *
     * @return int|string
     */
    public function id(): int|string
    {
        return $this->id;
    }
    
    /**
     * Returns the action.
     *
     * @return null|ActionInterface
     */
    public function action(): null|ActionInterface
    {
        return $this->action;
    }
}