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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action\Actions;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Action\BulkActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Entity\Entities;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Exception\ActionNotFoundException;
use Tobento\App\Crud\Exception\EntityNotFoundException;
use Tobento\App\Crud\Exception\EntityUndeletableException;
use Tobento\App\Crud\Exception\EntityUnupdatableException;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Iterable\Iter;
use Tobento\Service\Support\Arrayable;

/**
 * AbstractCrudController
 */
abstract class AbstractCrudController
{
    use InteractsWithRequestTrait;
    
    /**
     * @var RepositoryInterface
     */
    protected RepositoryInterface $repository;
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    abstract protected function configureFields(ActionInterface $action): iterable|FieldsInterface;
    
    /**
     * Returns the configured actions.
     *
     * @return iterable<ActionInterface>|ActionsInterface
     */
    abstract protected function configureActions(): iterable|ActionsInterface;
    
    /**
     * Returns the configured filters.
     *
     * @param ActionInterface $action
     * @return iterable<FilterInterface>|FiltersInterface
     */
    abstract protected function configureFilters(ActionInterface $action): iterable|FiltersInterface;

    /**
     * Returns the resource name. Must be unique, lowercase and only of [a-z-] characters.
     *
     * @return string
     */
    public function resourceName(): string
    {
        if (defined(sprintf('%s::%s', static::class, 'RESOURCE_NAME'))) {
            return static::RESOURCE_NAME;
        }
        
        throw new \LogicException('Define a unique resource name');
    }
    
    /**
     * Returns the entity id name.
     *
     * @return string
     */
    protected function entityIdName(): string
    {
        return 'id';
    }
    
    /**
     * Returns the repository.
     *
     * @return RepositoryInterface
     */
    public function repository(): RepositoryInterface
    {
        return $this->repository;
    }
    
    /**
     * Create entity from object.
     *
     * @param object $object
     * @return EntityInterface
     */
    public function createEntityFromObject(object $object): EntityInterface
    {
        if ($object instanceof Arrayable) {
            return new Entity(
                attributes: $object->toArray(),
                idAttributeName: $this->entityIdName(),
            );
        }
        
        if (
            method_exists($object, 'toArray')
            && is_array($array = $object->toArray())
        ) {
            return new Entity(
                attributes: $array,
                idAttributeName: $this->entityIdName(),
            );
        }
        
        return new Entity(
            attributes: (array)$object,
            idAttributeName: $this->entityIdName(),
        );
    }
    
    /**
     * Returns the index response.
     *
     * @param ActionProcessorInterface $actionProcessor
     * @param FilterProcessorInterface $filterProcessor
     * @return ResponseInterface
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function index(
        ActionProcessorInterface $actionProcessor,
        FilterProcessorInterface $filterProcessor,
        ResponserInterface $responser,
    ): ResponseInterface {
        // Get the action:
        $actions = $this->getConfiguredActions();
        $action = $actions->get(name: 'index');

        if (is_null($action)) {
            throw new ActionNotFoundException(actionName: 'index');
        }
        
        $action->setController($this);
        $action->setActions($actions);
        $actionProcessor->preprocessAction(action: $action);
        
        // Set the configured fields if none specified:
        if ($action->fields()->empty()) {
            $action->setFields($this->getConfiguredFields(action: $action));
        }
        
        // Handle filters:
        if ($action->filters()->empty()) {
            $action->setFilters($this->getConfiguredFilters($action));
        }

        $action->setFilters($filterProcessor->processFilters(filters: $action->filters(), action: $action));
        
        // Handle Entities:
        $entities = new Entities($this->findEntities($action->filters()));
        
        $entities = $entities->map(function(object $item): EntityInterface {
            return $this->createEntityFromObject($item);
        });

        $action->setEntities($entities);
        
        // Process action:
        $this->isActionProcessable($action);
        $actionProcessor->processAction(action: $action);
        
        // Bulks:
        $bulkActions = $actions->bulks();
        
        foreach($bulkActions as $bulkAction) {
            $bulkAction->setActions($actions);
            $bulkAction->setActionProcessor($actionProcessor);
        }
        
        return $responser->render(
            view: $action->getView(),
            data: [
                'action' => $action->setFields($action->fields()->parent(null)),
                'buttons' => $action->buttons(),
                'filters' => $action->filters(),
                'bulkActions' => $bulkActions,
                'locale' => 'en',
            ],
        );
    }

    /**
     * Returns the bulk response.
     *
     * @param string $name
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function bulk(
        string $name,
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        // Get the action:
        $actions = $this->getConfiguredActions();
        $action = $actions->get(name: $name);
        
        if (is_null($action) || !$action instanceof BulkActionInterface) {
            throw new ActionNotFoundException(actionName: $name);
        }

        $action->setController($this);
        $action->setActions($actions);
        $actionProcessor->preprocessAction(action: $action);
        
        // Set the configured fields if none specified:
        if ($action->fields()->empty()) {
            $action->setFields($this->getConfiguredFields(action: $action));
        }
        
        if ($action->name() === 'bulk-delete') {
            $action->setFields($action->fields());
        } else {
            $action->setFields($action->fields()->editable());
        }
        
        $action->setInput(new Input($requester->input()->all()));
        
        // Process action:
        $this->isActionProcessable($action);
        $actionProcessor->processAction(action: $action);

        // Bulk process:
        $action->setActionProcessor($actionProcessor);

        $response = $actionProcessor->call($action->getBulkProcessAction());
        
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        return $responser->redirect(uri: $action->getLinkUrl());
    }

    /**
     * Returns the create response.
     *
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function create(
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        // Get the action:
        $actions = $this->getConfiguredActions();
        $action = $actions->get(name: 'create');

        if (is_null($action)) {
            throw new ActionNotFoundException(actionName: 'create');
        }
        
        $action->setController($this);
        $action->setActions($actions);
        $actionProcessor->preprocessAction(action: $action);
        
        // Handle entity:
        $action->setEntity(new Entity());

        // Handle input:
        $action->setInput(new Input($requester->input()->all()));
        
        // Set the configured fields if none specified:
        if ($action->fields()->empty()) {
            $action->setFields($this->getConfiguredFields(action: $action));
        }

        $action->setFields($action->fields()->creatable());
        
        // Process action:
        $this->isActionProcessable($action);
        $actionProcessor->processAction(action: $action);
        
        return $responser->render(
            view: $action->getView(),
            data: [
                'action' => $action->setFields($action->fields()->parent(null)),
            ],
        );
    }
    
    /**
     * Returns the store response.
     *
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function store(
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        // Get the action:
        $actions = $this->getConfiguredActions();
        $action = $actions->get(name: 'store');
        
        if (is_null($action)) {
            throw new ActionNotFoundException(actionName: 'store');
        }
        
        $action->setController($this);
        $action->setActions($actions);
        $actionProcessor->preprocessAction(action: $action);
        
        // Handle input:
        $action->setInput(new Input(
            array_replace_recursive($requester->input()->all(), $requester->request()->getUploadedFiles())
        ));
        
        // Set the configured fields if none specified:
        if ($action->fields()->empty()) {
            $action->setFields($this->getConfiguredFields(action: $action));
        }
        
        $fields = $action->fields()->creatable();
        
        if ($this->isLiveRequest($requester)) {
            $fields = $this->filterRequestedFieldsOnly($requester, $fields);
        }
        
        $action->setFields($fields);
        
        // Process action:
        $this->isActionProcessable($action);
        $actionProcessor->processAction(action: $action);
        
        if ($this->isLiveRequest($requester)) {
            $response = $this->create(
                actionProcessor: $actionProcessor,
                requester: $requester,
                responser: $responser,
            );
            
            return $responser->json([
                'status' => $response->getStatusCode(),
                'html' => (string)$response->getBody(),
            ]);
        }
        
        // Create entity:
        $attributes = $action->getInput()
            ->collection()
            ->onlyPresent($action->fields()->storable()->getNames())
            ->all();
        
        $entity = $this->storeEntity($attributes);
        $entity = $this->createEntityFromObject($entity);
        
        // Process stored fields action:
        $actionProcessor->processFieldsAction(
            action: $action,
            actionName: 'stored',
            entity: $entity,
        );
        
        // Handle next action:
        $this->handleNextAction($action, $actions, $entity, $actionProcessor);
        
        // Return the response:
        return $responser->redirect(uri: $action->getLinkUrl());
    }
    
    /**
     * Returns the edit response.
     *
     * @param int|string $id
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function edit(
        int|string $id,
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        // Get the action:
        $actions = $this->getConfiguredActions();
        $action = $actions->get(name: 'edit');

        if (is_null($action)) {
            throw new ActionNotFoundException(actionName: 'edit');
        }
        
        $action->setController($this);
        $action->setActions($actions);
        $actionProcessor->preprocessAction(action: $action);
        
        // Handle entity:
        $entity = $this->repository()->findById($id);
        
        if ($entity === null) {
            throw new EntityNotFoundException($id, $action);
        }

        $action->setEntity($this->createEntityFromObject($entity));
        
        // Handle input:
        $action->setInput(new Input($requester->input()->all()));

        // Set the configured fields if none specified:
        if ($action->fields()->empty()) {
            $action->setFields($this->getConfiguredFields(action: $action));
        }
        
        $action->setFields($action->fields()->editable());
        
        // Process action:
        $this->isActionProcessable($action);
        $actionProcessor->processAction(action: $action);
        
        return $responser->render(
            view: $action->getView(),
            data: [
                'action' => $action->setFields($action->fields()->parent(null)),
            ],
        );
    }
    
    /**
     * Returns the updated response.
     *
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function update(
        int|string $id,
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        // Get the action:
        $actions = $this->getConfiguredActions();
        $action = $actions->get(name: 'update');

        if (is_null($action)) {
            throw new ActionNotFoundException(actionName: 'update');
        }
        
        $action->setController($this);
        $action->setActions($actions);
        $actionProcessor->preprocessAction(action: $action);
        
        // Handle entity:
        $entity = $this->repository()->findById($id);
        
        if ($entity === null) {
            throw new EntityNotFoundException($id, $action);
        }
        
        $action->setEntity($this->createEntityFromObject($entity));
        
        // Handle input:
        $action->setInput(new Input(
            array_replace_recursive($requester->input()->all(), $requester->request()->getUploadedFiles())
        ));
        
        // Set the configured fields if none specified:
        if ($action->fields()->empty()) {
            $action->setFields($this->getConfiguredFields(action: $action));
        }
        
        $fields = $action->fields()->editable();
        
        if ($requester->isAjax() || $this->isLiveRequest($requester)) {
            $fields = $this->filterRequestedFieldsOnly($requester, $fields);
        }

        $action->setFields($fields);
        
        // Process action:
        $this->isActionProcessable($action);
        $actionProcessor->processAction(action: $action);

        if ($this->isLiveRequest($requester)) {
            $response = $this->edit(
                id: $id,
                actionProcessor: $actionProcessor,
                requester: $requester,
                responser: $responser,
            );
            
            return $responser->json([
                'status' => $response->getStatusCode(),
                'html' => (string)$response->getBody(),
            ]);
        }
        
        // Update entity:
        $attributes = $action->getInput()
            ->collection()
            ->onlyPresent($action->fields()->storable()->getNames())
            ->all();
        
        $updatedItem = $this->updateEntity($id, $attributes, $action->entity());
        $entity = $this->createEntityFromObject($updatedItem);
        
        // Process updated fields action:
        $actionProcessor->processFieldsAction(
            action: $action,
            actionName: 'updated',
            entity: $entity,
        );
        
        if ($requester->wantsJson()) {
            return $responser->json([
                'status' => 200,
                'entity' => $updatedItem->toArray(),
            ]);
        }
        
        // Handle next action:
        $this->handleNextAction($action, $actions, $entity, $actionProcessor);
        
        return $responser->redirect(uri: $action->getLinkUrl());
    }
    
    /**
     * Returns the copy response.
     *
     * @param int|string $id
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function copy(
        int|string $id,
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        // Get the action:
        $actions = $this->getConfiguredActions();
        $action = $actions->get(name: 'copy');

        if (is_null($action)) {
            throw new ActionNotFoundException(actionName: 'copy');
        }
        
        $action->setController($this);
        $action->setActions($actions);
        $actionProcessor->preprocessAction(action: $action);
        
        // Handle entity:
        $entity = $this->repository()->findById($id);
        
        if ($entity === null) {
            throw new EntityNotFoundException($id, $action);
        }

        $action->setEntity($this->createEntityFromObject($entity));
        
        // Handle input:
        $action->setInput(new Input($requester->input()->all()));

        // Set the configured fields if none specified:
        if ($action->fields()->empty()) {
            $action->setFields($this->getConfiguredFields(action: $action));
        }
        
        $action->setFields($action->fields()->creatable());
        
        // Process action:
        $this->isActionProcessable($action);
        $actionProcessor->processAction(action: $action);
        
        return $responser->render(
            view: $action->getView(),
            data: [
                'action' => $action->setFields($action->fields()->parent(null)),
            ],
        );
    }
    
    /**
     * Returns the show response.
     *
     * @param int|string $id
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function show(
        int|string $id,
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        // Get the action:
        $actions = $this->getConfiguredActions();
        $action = $actions->get(name: 'show');

        if (is_null($action)) {
            throw new ActionNotFoundException(actionName: 'show');
        }
        
        $action->setController($this);
        $action->setActions($actions);
        $actionProcessor->preprocessAction(action: $action);
        
        // Handle entity:
        $entity = $this->repository()->findById($id);
        
        if ($entity === null) {
            throw new EntityNotFoundException($id, $action);
        }
        
        $action->setEntity($this->createEntityFromObject($entity));

        // Show json:
        if ($requester->input()->get('type') === 'json') {
            $this->isActionProcessable($action);
            return $responser->json(data: $action->entity()->toArray());
        }
        
        // Set the configured fields if none specified:
        if ($action->fields()->empty()) {
            $action->setFields($this->getConfiguredFields(action: $action));
        }
        
        $action->setFields($action->fields()->showable());
        
        // Process action:
        $this->isActionProcessable($action);
        $actionProcessor->processAction(action: $action);
        
        return $responser->render(
            view: $action->getView(),
            data: [
                'action' => $action->setFields($action->fields()->parent(null)),
            ],
        );
    }
    
    /**
     * Returns the deleted response.
     *
     * @param int|string $id
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function delete(
        int|string $id,
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        // Get the action:
        $actions = $this->getConfiguredActions();
        $action = $actions->get(name: 'delete');

        if (is_null($action)) {
            throw new ActionNotFoundException(actionName: 'delete');
        }
        
        $action->setController($this);
        $action->setActions($actions);
        $actionProcessor->preprocessAction(action: $action);
        
        // Handle entity:
        $entity = $this->repository()->findById($id);
        
        if ($entity === null) {
            throw new EntityNotFoundException($id, $action);
        }
        
        $action->setEntity($this->createEntityFromObject($entity));

        // Set the configured fields if none specified:
        if ($action->fields()->empty()) {
            $action->setFields($this->getConfiguredFields(action: $action));
        }
        
        // Process action:
        $this->isActionProcessable($action);
        $actionProcessor->processAction(action: $action);
        
        // Delete entity:
        $this->deleteEntity(id: $id, entity: $action->entity());
        
        // Process deleted fields action:
        $actionProcessor->processFieldsAction(
            action: $action,
            actionName: 'deleted',
        );
        
        return $responser->redirect(uri: $action->getLinkUrl());
    }

    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return FieldsInterface
     */
    public function getConfiguredFields(ActionInterface $action): FieldsInterface
    {
        $fields = $this->configureFields($action);
        
        if (! $fields instanceof FieldsInterface) {
            $fields = new Fields(...Iter::toArray($fields));
        }
        
        return $fields;
    }
    
    /**
     * Returns the configured actions.
     *
     * @return ActionsInterface
     */
    public function getConfiguredActions(): ActionsInterface
    {
        $actions = $this->configureActions();
        
        if (! $actions instanceof ActionsInterface) {
            $actions = new Actions(...Iter::toArray($actions));
        }
        
        return $actions;
    }
    
    /**
     * Returns the configured filters.
     *
     * @param ActionInterface $action
     * @return FiltersInterface
     */
    public function getConfiguredFilters(ActionInterface $action): FiltersInterface
    {
        $filters = $this->configureFilters($action);
        
        if (! $filters instanceof FiltersInterface) {
            $filters = new Filters(...Iter::toArray($filters));
        }
        
        return $filters;
    }
    
    /**
     * Find entities.
     *
     * @param FiltersInterface $filters
     * @return iterable The found entities.
     */
    public function findEntities(FiltersInterface $filters): iterable
    {
        return $this->repository()->findAll(
            where: $filters->getWhereParameters(),
            orderBy: $filters->getOrderByParameters(),
            limit: $filters->getLimitParameter(),
        );
    }
    
    /**
     * Store entity.
     *
     * @param array $attributes
     * @return object The created entity
     */
    public function storeEntity(array $attributes): object
    {
        return $this->repository()->create(
            attributes: $attributes,
        );
    }
    
    /**
     * Update entity.
     *
     * @param int|string $id
     * @param array $attributes
     * @param EntityInterface $entity
     * @return object The updated entity
     */
    public function updateEntity(int|string $id, array $attributes, EntityInterface $entity): object
    {
        return $this->repository()->updateById(
            id: $id,
            attributes: $attributes,
        );
    }
    
    /**
     * Delete entity.
     *
     * @param int|string $id
     * @param EntityInterface $entity
     * @return void
     */
    public function deleteEntity(int|string $id, EntityInterface $entity): void
    {
        $this->repository()->deleteById(id: $id);
    }
    
    /**
     * Determines if action is processable.
     *
     * @param ActionInterface $action
     * @return void
     * @throws \Throwable
     */
    public function isActionProcessable(ActionInterface $action): void
    {
        // Check if entity can be updated:
        if ($action instanceof Action\Update && ! $action->isUpdatable($action->entity())) {
            throw new EntityUnupdatableException($action->entity()->id(), $action);
        }
        
        // Check if entity can be deleted:
        if ($action instanceof Action\Delete && ! $action->isDeletable($action->entity())) {
            throw new EntityUndeletableException($action->entity()->id(), $action);
        }
    }
    
    /**
     * Handles the next action.
     *
     * @param ActionInterface $action
     * @param ActionsInterface $actions
     * @param EntityInterface $entity
     * @param ActionProcessorInterface $actionProcessor
     * @return void
     */
    protected function handleNextAction(
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