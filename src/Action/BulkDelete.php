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

namespace Tobento\App\Crud\Action;

use Psr\Http\Message\ResponseInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\Exception\ActionNotFoundException;
use Tobento\App\Crud\Exception\ActionProcessException;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\View\ViewInterface;
use Throwable;

final class BulkDelete extends AbstractAction implements BulkActionInterface
{
    use HasActionProcessor;
    use Traits\HandleBulk;
    
    /**
     * Create a new BulkDelete.
     *
     * @param null|string $title
     */
    public function __construct(
        null|string $title = null,
    ) {
        $this->title = $title ?: 'Delete';
        $this->route('{name}.bulk', function(): array {
            return ['name' => $this->name()];
        });
        
        $this->linkToAction('index');
        $this->view('crud/bulk/delete');
    }
    
    /**
     * Returns the name. Must be sluggable and only of [a-z-] characters.
     *
     * @return string
     */
    public function name(): string
    {
        return 'bulk-delete';
    }
    
    /**
     * Returns the handler processing the action.
     *
     * @return callable(mixed...): \Psr\Http\Message\ResponseInterface
     */
    public function getHandler(): callable
    {
        return [$this, 'handle'];
    }
    
    /**
     * Handle action.
     *
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function handle(
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        return $this->handleBulk(
            action: $this,
            actionProcessor: $actionProcessor,
            requester: $requester,
            responser: $responser,
        );
    }

    /**
     * Returns the process bulk action.
     *
     * @return callable
     */
    public function getBulkProcessAction(): callable
    {
        return [$this, 'processBulk'];
    }
    
    /**
     * Process bulk action.
     *
     * @param ResponserInterface $responser
     * @return void
     * @throws ActionProcessException
     * @psalm-suppress RedundantCondition
     * @psalm-suppress NoValue
     */
    public function processBulk(ResponserInterface $responser): void
    {
        $input = $this->getInput();
        $repository = $this->controller()->repository();
        $deleteAction = $this->actions()->get('delete');
        
        if (! $deleteAction instanceof Action\Delete) {
            throw new ActionNotFoundException(actionName: 'delete');
        }
        
        $deleteAction->setFields($this->fields());
        
        $ids = $input->get('ids', []);
        
        foreach(array_values($ids) as $id) {
            if (!is_string($id) && !is_int($id)) {
                continue;
            }
            
            $entity = $repository->findById(id: $id);
            
            if (is_object($entity)) {
                $entity = $this->controller()->createEntityFromObject($entity);
                $deleteAction->setEntity($entity);
            } else {
                continue;
            }
            
            // Check if action is processable:
            try {
                $this->controller()->isActionProcessable($deleteAction);
            } catch (Throwable $e) {
                $responser->messages()->add(
                    level: 'error',
                    message: $e->getMessage(),
                );
                
                continue;
            }
            
            $this->actionProcessor()->processFields(action: $deleteAction, entity: $entity);
            
            // Delete entity:
            $this->controller()->deleteEntity(id: $id, entity: $deleteAction->entity());
            
            // Process deleted fields action:
            $this->actionProcessor()->processFieldsAction(
                action: $deleteAction,
                actionName: 'deleted',
            );
        }
    }    
    
    /**
     * Returns the html of action. MUST be escaped.
     *
     * @param ViewInterface $view
     * @return string
     */
    public function render(ViewInterface $view): string
    {
        return $view->render(
            view: $this->getView(),
            data: [
                'action' => $this,
            ],
        );
    }
    
    /**
     * Returns whether to display the button to perform the action.
     *
     * @return bool
     */
    public function displayButton(): bool
    {
        return true;
    }
}