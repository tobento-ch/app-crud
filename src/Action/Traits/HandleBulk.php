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
use Tobento\App\Crud\Input\Input;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;

trait HandleBulk
{
    /**
     * Handles the bulk action.
     *
     * @param ActionInterface $action
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function handleBulk(
        ActionInterface $action,
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        $controller = $action->controller();
        
        $actionProcessor->preprocessAction(action: $action);
        
        // Set the configured fields if none specified:
        if ($action->fields()->empty()) {
            $action->setFields($controller->getConfiguredFields(action: $action));
        }
        
        if ($action->name() === 'bulk-delete') {
            $action->setFields($action->fields());
        } else {
            $action->setFields($action->fields()->editable());
        }
        
        $action->setInput(new Input($requester->input()->all()));
        
        // Process action:
        $controller->isActionProcessable($action);
        $actionProcessor->processAction(action: $action);

        // Bulk process:
        $action->setActionProcessor($actionProcessor);

        $response = $actionProcessor->call($action->getBulkProcessAction());
        
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        return $responser->redirect(uri: $action->getLinkUrl());
    }
}