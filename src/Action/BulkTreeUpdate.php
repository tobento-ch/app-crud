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
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Exception\ActionNotFoundException;
use Tobento\App\Crud\Exception\ActionProcessException;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Input\Input;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\View\ViewInterface;
use Closure;
use Throwable;

final class BulkTreeUpdate extends AbstractAction implements BulkActionInterface
{
    use HasActionProcessor;
    
    private string $idName = 'id';
    
    private string $parentIdName = 'parent_id';
    
    private string $sortorderName = 'sortorder';
    
    /**
     * Create a new BulkTreeUpdate.
     *
     * @param null|string $title
     */
    public function __construct(
        null|string $title = null,
    ) {
        $this->title = $title ?: 'Tree Update';
        $this->route('{name}.bulk', function(): array {
            return ['name' => $this->name()];
        });
        
        $this->linkToAction('index');
        $this->view('crud/bulk/tree-update');
    }
    
    /**
     * Sets the field mapping.
     *
     * @param null|string $id
     * @param null|string $parentId
     * @param null|string $sortorder
     * @return static $this
     */
    public function mapping(null|string $id = null, null|string $parentId = null, null|string $sortorder = null): static
    {
        if (!empty($id)) {
            $this->idName = $id;
        }
        
        if (!empty($parentId)) {
            $this->parentIdName = $parentId;
        }
        
        if (!empty($sortorder)) {
            $this->sortorderName = $sortorder;
        }
        
        return $this;
    }

    /**
     * Returns the id name.
     *
     * @return string
     */
    public function idName(): string
    {
        return $this->idName;
    }
    
    /**
     * Returns the parent id name.
     *
     * @return string
     */
    public function parentIdName(): string
    {
        return $this->parentIdName;
    }
    
    /**
     * Returns the sortorder name.
     *
     * @return string
     */
    public function sortorderName(): string
    {
        return $this->sortorderName;
    }
    
    /**
     * Returns the name. Must be sluggable and only of [a-z-] characters.
     *
     * @return string
     */
    public function name(): string
    {
        return 'bulk-tree-update';
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
     * @return ResponseInterface
     * @throws ActionProcessException
     */
    public function processBulk(ResponserInterface $responser): ResponseInterface
    {
        $input = $this->getInput();
        $repository = $this->controller()->repository();
        $updateAction = $this->actions()->get('update');
        
        if (! $updateAction instanceof Action\Update) {
            throw new ActionNotFoundException(actionName: 'update');
        }
        
        $fields = $this->fields()->filter(
            fn (FieldInterface $f): bool => in_array($f->name(), [$this->parentIdName(), $this->sortorderName()])
        );
        
        $updateAction->setFields($fields);
        
        $items = $input->get('items', []);
        $updatedItems = [];
        
        foreach($items as $item) {
            
            if (!is_array($item)) {
                continue;
            }
            
            $id = $item['id'] ?? 0;
            $parentId = $item['parent_id'] ?? 0;
            $sortorder = $item['sortorder'] ?? 0;
            
            $entity = $repository->findById(id: $id);
            
            if (is_null($entity)) {
                continue;
            }
            
            $attributes = [
                $this->parentIdName => $parentId,
                $this->sortorderName => $sortorder,
            ];
            
            // set input for validation:
            $updateAction->setInput(new Input($attributes));
            
            $entity = $this->controller()->createEntityFromObject($entity);
            $updateAction->setEntity($entity);
            
            $this->actionProcessor()->processFields(action: $updateAction, entity: $entity);
            
            $updatedItem = $this->controller()->updateEntity($id, $attributes, $updateAction->entity());
            $entity = $this->controller()->createEntityFromObject($updatedItem);
            
            $updatedItems[] = [
                'id' => $entity->get($this->idName()),
                'parent_id' => $entity->get($this->parentIdName()),
                'sortorder' => $entity->get($this->sortorderName()),
            ];
            
            // Process updated fields action:
            $this->actionProcessor()->processFieldsAction(
                action: $updateAction,
                actionName: 'updated',
                entity: $entity,
            );
        }
        
        return $responser->json([
            'status' => 200,
            'items' => $updatedItems,
            'parentIdName' => $this->parentIdName(),
            'sortorderName' => $this->sortorderName(),
        ]);
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
        return false;
    }
}