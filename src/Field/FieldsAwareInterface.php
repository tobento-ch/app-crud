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

use Tobento\App\Crud\Action\ActionInterface;

/**
 * FieldsAwareInterface
 */
interface FieldsAwareInterface
{
    /**
     * Returns the fields.
     *
     * @param ActionInterface $action
     * @return FieldsInterface
     */
    public function getFields(ActionInterface $action): FieldsInterface;
}