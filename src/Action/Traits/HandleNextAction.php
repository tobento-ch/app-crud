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

namespace Tobento\App\Crud\Action\Traits;

use Psr\Http\Message\ResponseInterface;
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\Service\Requester\RequesterInterface;

trait HandleNextAction
{
    /**
     * Handles the next action.
     *
     * @param ActionInterface $action
     * @param ActionsInterface $actions
     * @param EntityInterface $entity
     * @param ActionProcessorInterface $actionProcessor
     * @return null|ResponseInterface
     */
    public function handleNextAction(
        ActionInterface $action,
        ActionsInterface $actions,
        EntityInterface $entity,
        RequesterInterface $requester,
        ActionProcessorInterface $actionProcessor,
    ): null|ResponseInterface {
        $nextActionName = $action->getInput()->get('next_action');

        if (!is_string($nextActionName)) {
            return null;
        }

        [$actionName, $buttonName] = array_pad(explode('|', $nextActionName), 2, null);

        $nextAction = $actions->get(name: $actionName);

        if (is_null($nextAction)) {
            return null;
        }

        // Determine the method used for chaining
        $method = $requester->method();

        // If next action supports this method execute it
        if (
            !in_array($method, ['GET', 'HEAD', 'OPTIONS'])
            && $nextAction->supportsRequestMethod($method)
        ) {
            // Execute next action and return its response
            $nextAction->setActions($action->actions());
            $nextAction->setController($action->controller());
            
            return $actionProcessor->call($nextAction->getHandler(), ['id' => $entity->id()]);
        }

        // Otherwise redirect to next action
        $nextAction->setEntity($entity);
        $actionProcessor->resolveActionUrls(action: $nextAction);
        $action->setLinkUrl($nextAction->getUrl());

        if ($buttonName && !is_null($button = $nextAction->buttons()->get($buttonName))) {
            $url = $actionProcessor->urlResolver()->resolveButtonUrl($button, $nextAction, $entity);
            $action->setLinkUrl($url);
        }
        
        return null;
    }
}