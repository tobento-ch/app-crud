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

use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Entity\EntityInterface;

trait HandleNextAction
{
    /**
     * Handles the next action.
     *
     * @param ActionInterface $action
     * @param ActionsInterface $actions
     * @param EntityInterface $entity
     * @param ActionProcessorInterface $actionProcessor
     * @return void
     */
    public function handleNextAction(
        ActionInterface $action,
        ActionsInterface $actions,
        EntityInterface $entity,
        ActionProcessorInterface $actionProcessor,
    ): void {
        $nextActionName = $action->getInput()->get('next_action');
        
        if (!is_string($nextActionName)) {
            return;
        }
        
        [$actionName, $buttonName] = array_pad(explode('|', $nextActionName), 2, null);
        
        if (!is_null($nextAction = $actions->get(name: $actionName))) {
            $nextAction->setEntity($entity);
            $actionProcessor->resolveActionUrls(action: $nextAction);
            $action->setLinkUrl($nextAction->getUrl());
            
            if ($buttonName && !is_null($button = $nextAction->buttons()->get($buttonName))) {
                $url = $actionProcessor->urlResolver()->resolveButtonUrl($button, $nextAction, $entity);
                $action->setLinkUrl($url);
            }
        }
    }
}