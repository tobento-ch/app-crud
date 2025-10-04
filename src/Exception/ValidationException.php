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

use Tobento\Service\Validation\ValidationInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Throwable;

/**
 * ValidationException
 */
class ValidationException extends ActionProcessException
{
    /**
     * Create a new ValidationException.
     *
     * @param ValidationInterface $validation
     * @param ActionInterface $action
     * @param null|string $redirectActionName
     * @param string $message The message
     * @param int $code
     * @param null|Throwable $previous
     */
    public function __construct(
        protected ValidationInterface $validation,
        protected ActionInterface $action,
        protected null|string $redirectActionName = null,
        string $message = '',
        int $code = 0,
        null|Throwable $previous = null
    ) {
        if ($message === '') {
            $message = (string)$validation->errors()->first()?->message();
        }
        
        parent::__construct($action, $message, $code, $previous);
    }
    
    /**
     * Returns the validation.
     *
     * @return ValidationInterface
     */
    public function validation(): ValidationInterface
    {
        return $this->validation;
    }
    
    /**
     * Returns the redirect action name.
     *
     * @return null|string
     */
    public function redirectActionName(): null|string
    {
        return $this->redirectActionName;
    }
}