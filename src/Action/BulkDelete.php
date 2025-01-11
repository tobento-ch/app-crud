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

use Tobento\App\Crud\Action;
use Tobento\App\Crud\Exception\ActionNotFoundException;
use Tobento\App\Crud\Exception\ActionProcessException;
use Tobento\Service\View\ViewInterface;

/**
 * BulkDelete
 */
final class BulkDelete extends AbstractAction implements BulkActionInterface
{
    use HasActionProcessor;
    
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
     * Create a new instance.
     *
     * @param null|string $title
     * @return static
     */
    public static function new(null|string $title = null): static
    {
        return new static($title);
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
     * @return void
     * @throws ActionProcessException
     */
    public function processBulk(): void
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
                        
            $entity = $repository->findById(id: $id);
            
            if (is_object($entity)) {
                $entity = $this->controller()->createEntityFromObject($entity);
                $deleteAction->setEntity($entity);
            } else {
                continue;
            }
            
            // Check if entity can be deleted:
            if (! $deleteAction->isDeletable($deleteAction->entity())) {
                continue;
            }
            
            $this->actionProcessor()->processFields(action: $deleteAction, entity: $entity);
            
            $repository->deleteById(id: $id);
            
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
}