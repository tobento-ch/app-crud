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

namespace Tobento\App\Crud\Field;

/**
 * PrimaryId
 */
class PrimaryId extends Text
{
    /**
     * Configure field.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->creatable(false);
        $this->editable(false);
        $this->showable(false);
    }
}