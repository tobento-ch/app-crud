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
 * ResourceTypeNotFoundException
 */
class ResourceTypeNotFoundException extends RuntimeException
{
    /**
     * Create a new ResourceTypeNotFoundException.
     *
     * @param string $type
     * @param string $message The message
     * @param int $code
     * @param null|Throwable $previous
     */
    public function __construct(
        protected string $type,
        string $message = '',
        int $code = 0,
        null|Throwable $previous = null
    ) {
        if ($message === '') {            
            $message = sprintf('Resource type %s not found.', $type);
        }
        
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Returns the type.
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }
}