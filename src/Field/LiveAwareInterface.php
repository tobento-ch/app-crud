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

interface LiveAwareInterface
{
    /**
     * Returns the after live handler.
     *
     * @param string $action
     * @return null|callable
     */
    public function getAfterLiveHandler(string $action): null|callable;
    
    /**
     * Returns the live parameters for the given action.
     *
     * @param string $action
     * @return array
     */
    public function getLiveParameters(string $action): array;

    /**
     * Returns whether live parameters for the given action exists or not.
     *
     * @param string $action
     * @return bool
     */
    public function hasLiveParameters(string $action): bool;
    
    /**
     * Assigns live attributes for the given action.
     *
     * @param string $action
     * @param array $attributes
     * @return array
     */
    public function assignLiveAttributes(string $action, array $attributes): array;
}