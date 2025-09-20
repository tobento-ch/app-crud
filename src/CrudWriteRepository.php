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

use Closure;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Exception\ActionNotFoundException;
use Tobento\App\Crud\Exception\EntityNotFoundException;
use Tobento\App\Crud\Exception\EntityUndeletableException;
use Tobento\App\Crud\Exception\EntityUnupdatableException;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Input\Input;
use Tobento\Service\Repository\RepositoryCreateException;
use Tobento\Service\Repository\RepositoryDeleteException;
use Tobento\Service\Repository\RepositoryUpdateException;
use Tobento\Service\Repository\WriteRepositoryInterface;
use Throwable;

class CrudWriteRepository implements WriteRepositoryInterface
{
    /**
     * @var array<array-key, string>
     */
    protected array $onlyFields = [];
    
    /**
     * @var array<array-key, string>
     */
    protected array $exceptFields = [];
    
    /**
     * @var null|Closure|FieldsInterface
     */
    protected null|Closure|FieldsInterface $withFields = null;
    
    /**
     * @var array<array-key, string>
     */
    protected array $onlyActions = ['store', 'update', 'delete'];
    
    /**
     * Create a new CrudWriteRepository instance.
     *
     * @param AbstractCrudController $controller
     * @param ActionProcessorInterface $actionProcessor
     */
    public function __construct(
        protected AbstractCrudController $controller,
        protected ActionProcessorInterface $actionProcessor,
    ) {}    

    /**
     * Returns the controller.
     *
     * @return AbstractCrudController
     */
    public function controller(): AbstractCrudController
    {
        return $this->controller;
    }
    
    /**
     * Returns the action processor.
     *
     * @return ActionProcessorInterface
     */
    public function actionProcessor(): ActionProcessorInterface
    {
        return $this->actionProcessor;
    }
    
    /**
     * Returns a new instance with the specified field(s) only.
     *
     * @param string ...$names
     * @return static
     */
    public function onlyFields(string ...$names): static
    {
        $new = clone $this;
        $new->onlyFields = $names;
        return $new;
    }
    
    /**
     * Returns a new instance except the specified field(s).
     *
     * @param string ...$names
     * @return static
     */
    public function exceptFields(string ...$names): static
    {
        $new = clone $this;
        $new->exceptFields = $names;
        return $new;
    }
    
    /**
     * Returns a new instance with the specified field(s).
     *
     * @param Closure|FieldsInterface $fields
     * @return static
     */
    public function withFields(Closure|FieldsInterface $fields): static
    {
        $new = clone $this;
        $new->withFields = $fields;
        return $new;
    }
    
    /**
     * Returns a new instance with the specified action(s) only.
     *
     * @param string ...$names
     * @return static
     */
    public function onlyActions(string ...$names): static
    {
        $new = clone $this;
        $new->onlyActions = $names;
        return $new;
    }
    
    /**
     * Create an entity.
     *
     * @param array $attributes
     * @return EntityInterface The created entity.
     * @throws RepositoryCreateException
     */
    public function create(array $attributes): EntityInterface
    {
        try {
            return $this->createAction(attributes: $attributes);
        } catch (Throwable $e) {
            throw new RepositoryCreateException(
                attributes: $attributes,
                message: $e->getMessage(),
                previous: $e,
            );
        }
    }
    
    /**
     * Create an entity.
     *
     * @param array $attributes
     * @return EntityInterface The created entity.
     */
    protected function createAction(array $attributes): EntityInterface
    {
        // Get the action:
        $actions = $this->getConfiguredActions();
        $action = $actions->get(name: 'store');
        
        if (is_null($action)) {
            throw new ActionNotFoundException(actionName: 'store');
        }
        
        $action->setController($this->controller);
        $action->setActions($actions);
        $this->actionProcessor->preprocessAction(action: $action);
        
        // Handle input:
        $action->setInput(new Input($attributes));

        // Set the configured fields if none specified:
        if ($action->fields()->empty()) {
            $action->setFields($this->getConfiguredFields(action: $action));
        }
        
        $action->setFields($action->fields()->creatable());
        
        $this->modifyInput($action);
        
        // Process action:
        $this->actionProcessor->processAction(action: $action);
        
        // Create entity:
        $attributes = $action->getInput()
            ->collection()
            ->onlyPresent($action->fields()->storable()->getNames())
            ->all();
        
        $entity = $this->controller->storeEntity($attributes);
        $entity = $this->controller->createEntityFromObject($entity);
        
        // Process stored fields action:
        $this->actionProcessor->processFieldsAction(
            action: $action,
            actionName: 'stored',
            entity: $entity,
        );
        
        return $entity;
    }

    /**
     * Update an entity by id.
     *
     * @param string|int $id
     * @param array $attributes The attributes to update the entity.
     * @return EntityInterface The updated entity.
     * @throws RepositoryUpdateException
     */
    public function updateById(string|int $id, array $attributes): EntityInterface
    {
        try {
            return $this->updateByIdAction(id: $id, attributes: $attributes);
        } catch (Throwable $e) {
            throw new RepositoryUpdateException(
                attributes: $attributes,
                id: $id,
                message: $e->getMessage(),
                previous: $e,
            );
        }
    }
    
    /**
     * Update an entity by id.
     *
     * @param string|int $id
     * @param array $attributes The attributes to update the entity.
     * @return EntityInterface The updated entity.
     */
    protected function updateByIdAction(string|int $id, array $attributes): EntityInterface
    {
        // Get the action:
        $actions = $this->getConfiguredActions();
        $action = $actions->get(name: 'update');

        if (is_null($action)) {
            throw new ActionNotFoundException(actionName: 'update');
        }
        
        $action->setController($this->controller);
        $action->setActions($actions);
        $this->actionProcessor->preprocessAction(action: $action);
        
        // Handle entity:
        $entity = $this->controller->repository()->findById($id);
        
        if ($entity === null) {
            throw new EntityNotFoundException($id, $action);
        }
        
        $action->setEntity($this->controller->createEntityFromObject($entity));
        
        // Check if entity can be updated:
        if ($action instanceof Action\Update && ! $action->isUpdatable($action->entity())) {
            throw new EntityUnupdatableException($id, $action);
        }
        
        // Handle input:
        $action->setInput(new Input($attributes));
        
        // Set the configured fields if none specified:
        if ($action->fields()->empty()) {
            $action->setFields($this->getConfiguredFields(action: $action));
        }
        
        $action->setFields($action->fields()->editable());
        
        $this->modifyInput($action);
        
        // Process action:
        $this->actionProcessor->processAction(action: $action);

        // Update entity:
        $attributes = $action->getInput()
            ->collection()
            ->onlyPresent($action->fields()->storable()->getNames())
            ->all();
        
        $updatedItem = $this->controller->updateEntity($id, $attributes, $action->entity());
        $entity = $this->controller->createEntityFromObject($updatedItem);
        
        // Process updated fields action:
        $this->actionProcessor->processFieldsAction(
            action: $action,
            actionName: 'updated',
            entity: $entity,
        );
        
        return $entity;
    }
    
    /**
     * Update entities.
     *
     * @param array $where The where parameters.
     * @param array $attributes The attributes to update the entities.
     * @return iterable<object> The updated entities.
     * @throws RepositoryUpdateException
     */
    public function update(array $where, array $attributes): iterable
    {
        throw new RepositoryUpdateException(message: 'Unsupported');
    }

    /**
     * Delete an entity by id.
     *
     * @param string|int $id
     * @return EntityInterface The deleted entity.
     * @throws RepositoryDeleteException
     */
    public function deleteById(string|int $id): EntityInterface
    {
        try {
            return $this->deleteByIdAction(id: $id);
        } catch (Throwable $e) {
            throw new RepositoryDeleteException(
                id: $id,
                message: $e->getMessage(),
                previous: $e,
            );
        }
    }
    
    /**
     * Delete an entity by id.
     *
     * @param string|int $id
     * @return EntityInterface The deleted entity.
     * @throws RepositoryDeleteException
     */
    protected function deleteByIdAction(string|int $id): EntityInterface
    {
        // Get the action:
        $actions = $this->getConfiguredActions();
        $action = $actions->get(name: 'delete');

        if (is_null($action)) {
            throw new ActionNotFoundException(actionName: 'delete');
        }
        
        $action->setController($this->controller);
        $action->setActions($actions);
        $this->actionProcessor->preprocessAction(action: $action);
        
        // Handle entity:
        $entity = $this->controller->repository()->findById($id);
        
        if ($entity === null) {
            throw new EntityNotFoundException($id, $action);
        }
        
        $action->setEntity($this->controller->createEntityFromObject($entity));
        
        // Check if entity can be deleted:
        if ($action instanceof Action\Delete && ! $action->isDeletable($action->entity())) {
            throw new EntityUndeletableException($id, $action);
        }

        // Set the configured fields if none specified:
        if ($action->fields()->empty()) {
            $action->setFields($this->getConfiguredFields(action: $action));
        }
        
        // Process action:
        $this->actionProcessor->processAction(action: $action);
        
        // Delete entity:
        $this->controller->deleteEntity(id: $id, entity: $action->entity());
        
        // Process deleted fields action:
        $this->actionProcessor->processFieldsAction(
            action: $action,
            actionName: 'deleted',
        );
        
        return $action->entity();
    }
    
    /**
     * Delete entities.
     *
     * @param array $where The where parameters.
     * @return iterable<object> The deleted entities.
     * @throws RepositoryDeleteException
     */
    public function delete(array $where): iterable
    {
        throw new RepositoryDeleteException(message: 'Unsupported');
    }
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return FieldsInterface
     */
    public function getConfiguredFields(ActionInterface $action): FieldsInterface
    {
        return $this->configureFields(
            fields: $this->controller->getConfiguredFields(action: $action),
            action: $action,
        );
    }
    
    /**
     * Returns the configured actions.
     *
     * @return ActionsInterface
     */
    public function getConfiguredActions(): ActionsInterface
    {
        return $this->configureActions(
            actions: $this->controller->getConfiguredActions(),
        );
    }
    
    /**
     * Returns the configured fields.
     *
     * @param FieldsInterface $fields
     * @param ActionInterface $action
     * @return FieldsInterface
     */
    protected function configureFields(FieldsInterface $fields, ActionInterface $action): FieldsInterface
    {
        if ($this->withFields instanceof Closure) {
            $fields = ($this->withFields)($fields, $action);
        } else if ($this->withFields instanceof FieldsInterface) {
            $fields = $this->withFields;
        }
        
        if (!empty($this->onlyFields)) {
            $fields = $fields->filter(fn (FieldInterface $f): bool => in_array($f->name(), $this->onlyFields));
        }
        
        if (!empty($this->exceptFields)) {
            $fields = $fields->filter(fn (FieldInterface $f): bool => !in_array($f->name(), $this->exceptFields));
        }
        
        return $fields;
    }
    
    /**
     * Returns the configured actions.
     *
     * @param ActionsInterface $actions
     * @return ActionsInterface
     */
    protected function configureActions(ActionsInterface $actions): ActionsInterface
    {
        return $actions->filter(fn (ActionInterface $a): bool => in_array($a->name(), $this->onlyActions));
    }
    
    /**
     * Modifies the input.
     *
     * @param ActionInterface $action
     * @return void
     */
    protected function modifyInput(ActionInterface $action): void
    {
        //
    }
}