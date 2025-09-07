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

namespace Tobento\App\Crud;

use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Exception\ActionProcessException;
use Tobento\App\Crud\Url\UrlResolverInterface;

/**
 * ActionProcessorInterface
 */
interface ActionProcessorInterface
{
    /**
     * Returns the url resolver.
     *
     * @return UrlResolverInterface
     */
    public function urlResolver(): UrlResolverInterface;
    
    /**
     * Preprocess action.
     *
     * @param ActionInterface $action
     * @return void
     * @throws ActionProcessException
     */
    public function preprocessAction(ActionInterface $action): void;
    
    /**
     * Process action.
     *
     * @param ActionInterface $action
     * @return void
     * @throws ActionProcessException
     */
    public function processAction(ActionInterface $action): void;
    
    /**
     * Process fields.
     *
     * @param ActionInterface $action
     * @param null|EntityInterface $entity
     * @return void
     */
    public function processFields(
        ActionInterface $action,
        null|EntityInterface $entity = null,
    ): void;
    
    /**
     * Process fields action.
     *
     * @param ActionInterface $action
     * @param null|string $actionName
     * @param null|EntityInterface $entity
     * @return void
     * @throws ActionProcessException
     */
    public function processFieldsAction(
        ActionInterface $action,
        null|string $actionName = null,
        null|EntityInterface $entity = null,
    ): void;    
    
    /**
     * Resolves the action urls.
     *
     * @param ActionInterface $action
     * @return void
     */
    public function resolveActionUrls(ActionInterface $action): void;
    
    /**
     * Resolve callable and calls it.
     *
     * @param mixed $callable
     * @param array<int|string, mixed> $parameters
     * @return mixed The result of the callable.
     */
    public function call(mixed $callable, array $parameters = []): mixed;
}