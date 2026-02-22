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

use Serializable;
use Tobento\Service\Upload\Exception\UploadException;

/**
 * UploadErrorException
 */
class UploadErrorException extends UploadException implements Serializable
{
    public function serialize(): null|string
    {
        return null;
    }
    
    public function unserialize(string $data): void
    {
        //
    }
    
    public function __serialize(): array
    {
        return [];
    }
    
    public function __unserialize(array $data): void
    {
        //
    }
}