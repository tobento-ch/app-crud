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
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action\Actions;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action\BulkActionInterface;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Entity\Entities;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Exception\ActionNotFoundException;
use Tobento\App\Crud\Exception\EntityUndeletableException;
use Tobento\App\Crud\Exception\EntityUnupdatableException;
use Tobento\Service\Iterable\Iter;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Support\Arrayable;

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
        return $this->runAction(
            name: 'index',
            actionProcessor: $actionProcessor,
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
        return $this->runAction(
            name: $name,
            actionProcessor: $actionProcessor,
            requiredInterface: BulkActionInterface::class,
        );
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
        return $this->runAction(
            name: 'create',
            actionProcessor: $actionProcessor,
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
        return $this->runAction(
            name: 'store',
            actionProcessor: $actionProcessor,
        );
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
        return $this->runAction(
            name: 'edit',
            actionProcessor: $actionProcessor,
            params: ['id' => $id],
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
        return $this->runAction(
            name: 'update',
            actionProcessor: $actionProcessor,
            params: ['id' => $id],
        );
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
        return $this->runAction(
            name: 'copy',
            actionProcessor: $actionProcessor,
            params: ['id' => $id],
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
        return $this->runAction(
            name: 'show',
            actionProcessor: $actionProcessor,
            params: ['id' => $id],
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
        return $this->runAction(
            name: 'delete',
            actionProcessor: $actionProcessor,
            params: ['id' => $id],
        );
    }
    
    /**
     * Returns the dynamic action response.
     *
     * @param string $action
     * @param null|int|string $id
     * @param ActionProcessorInterface $actionProcessor
     * @return ResponseInterface
     */
    public function dynamic(
        string $action,
        null|int|string $id,
        ActionProcessorInterface $actionProcessor,
    ): ResponseInterface {
        return $this->runAction(
            name: $action,
            actionProcessor: $actionProcessor,
            params: ['id' => $id],
        );
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
     * Executes a CRUD action by name.
     *
     * @param string $name
     * @param ActionProcessorInterface $actionProcessor
     * @param array $params Parameters passed to the action handler
     * @param null|string $requiredInterface Optional interface the action must implement
     * @return ResponseInterface
     */
    protected function runAction(
        string $name,
        ActionProcessorInterface $actionProcessor,
        array $params = [],
        null|string $requiredInterface = null,
    ): ResponseInterface {
        $actions = $this->getConfiguredActions();
        $action = $actions->get(name: $name);

        if (is_null($action)) {
            throw new ActionNotFoundException(actionName: $name);
        }

        if ($requiredInterface !== null && !$action instanceof $requiredInterface) {
            throw new ActionNotFoundException(actionName: $name);
        }

        $action->setController($this);
        $action->setActions($actions);

        return $actionProcessor->call($action->getHandler(), $params);
    }
}