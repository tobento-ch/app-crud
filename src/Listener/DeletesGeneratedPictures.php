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

use Tobento\App\Crud\Event\FileSourceDeleted;
use Tobento\App\Media\Picture\PictureGeneratorInterface;

final class DeletesGeneratedPictures
{
    /**
     * Deletes all generated pictures after a crud file source has been deleted.
     *
     * @param FileSourceDeleted $event
     * @param PictureGeneratorInterface $pictureGenerator
     * @return void
     */
    public function __invoke(FileSourceDeleted $event, PictureGeneratorInterface $pictureGenerator): void
    {
        $pictureGenerator->pictureRepository()->deleteAllByPath(path: $event->path());
    }
}