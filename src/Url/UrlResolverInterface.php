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

namespace Tobento\App\Crud\Url;

use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Button\ButtonInterface;
use Tobento\App\Crud\Button\ButtonsInterface;
use Tobento\App\Crud\Entity\EntityInterface;

/**
 * UrlResolverInterface
 */
interface UrlResolverInterface
{
    /**
     * Returns the resolved buttons.
     *
     * @param ButtonsInterface $buttons
     * @param ActionInterface $action
     * @param null|EntityInterface $entity
     * @return ButtonsInterface
     */
    public function resolveButtonsUrl(
        ButtonsInterface $buttons,
        ActionInterface $action,
        null|EntityInterface $entity = null,
    ): ButtonsInterface;
    
    /**
     * Returns the resolved button url.
     *
     * @param ButtonInterface $button
     * @param ActionInterface $action
     * @param null|EntityInterface $entity
     * @return string
     */
    public function resolveButtonUrl(
        ButtonInterface $button,
        ActionInterface $action,
        null|EntityInterface $entity = null,
    ): string;
    
    /**
     * Returns the resolved action link to url.
     *
     * @param ActionInterface $action
     * @param null|EntityInterface $entity
     * @return string
     */
    public function resolveActionLinkToUrl(
        ActionInterface $action,
        null|EntityInterface $entity = null,
    ): string;
    
    /**
     * Returns the resolved action url.
     *
     * @param ActionInterface $action
     * @param null|EntityInterface $entity
     * @return string
     */
    public function resolveActionUrl(
        ActionInterface $action,
        null|EntityInterface $entity = null,
    ): string;
}