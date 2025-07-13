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

namespace Tobento\App\Crud\Event;

/**
 * Event after a file source has been deleted.
 */
final class FileSourceDeleted
{
    /**
     * Create a new FileSourceDeleted instance.
     *
     * @param string $path
     */
    public function __construct(
        private string $path,
    ) {}
    
    /**
     * Returns the path.
     *
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }
}