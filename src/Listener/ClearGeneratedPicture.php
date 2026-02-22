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

namespace Tobento\App\Crud\Listener;

use Tobento\App\Media\Event\ImageEdited;
use Tobento\Service\Picture\Generator\PictureRepositoryInterface;

/**
 * Clears the generated picture.
 */
final class ClearGeneratedPicture
{
    /**
     * Create a new ClearGeneratedPicture instance.
     *
     * @param string $definition
     */
    public function __construct(
        private string $definition = 'crud-file-src',
    ) {}
    
    /**
     * Create a new FilterProcessor.
     *
     * @param ImageEdited $event
     * @param PictureRepositoryInterface $repo
     * @return void
     */
    public function __invoke(ImageEdited $event, PictureRepositoryInterface $repo): void
    {
        $file = $event->file();
        
        $repo->delete(
            path: $file->path(),
            definition: $this->definition,
        );
    }
}