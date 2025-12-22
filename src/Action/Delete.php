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

use Closure;
use Psr\Http\Message\ResponseInterface;
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Exception\EntityNotFoundException;
use Tobento\Service\Responser\ResponserInterface;

final class Delete extends AbstractAction
{
    /**
     * @var null|callable(EntityInterface):bool|array<array-key, int|string>
     */
    private $undeletable = null;
    
    /**
     * @var callable|string
     */
    private $undeletableReason = '';
    
    /**
     * Create a new Delete.
     *
     * @param null|string|Closure $title
     */
    public function __construct(
        null|string|Closure $title = null,
    ) {
        $this->title = $title;
        $this->route('{name}.delete', function(EntityInterface $entity): array {
            return ['id' => $entity->id()];
        });
        
        $this->linkToAction('index');
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'delete';
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
     * @param int|string $id
     * @param ActionProcessorInterface $actionProcessor
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function handle(
        int|string $id,
        ActionProcessorInterface $actionProcessor,
        ResponserInterface $responser,
    ): ResponseInterface {
        $controller = $this->controller();
        
        $actionProcessor->preprocessAction(action: $this);
        
        // Handle entity:
        $entity = $controller->repository()->findById($id);
        
        if ($entity === null) {
            throw new EntityNotFoundException($id, $this);
        }
        
        $this->setEntity($controller->createEntityFromObject($entity));

        // Set the configured fields if none specified:
        if ($this->fields()->empty()) {
            $this->setFields($controller->getConfiguredFields(action: $this));
        }
        
        // Process action:
        $controller->isActionProcessable($this);
        $actionProcessor->processAction(action: $this);
        
        // Delete entity:
        $controller->deleteEntity(id: $id, entity: $this->entity());
        
        // Process deleted fields action:
        $actionProcessor->processFieldsAction(
            action: $this,
            actionName: 'deleted',
        );
        
        return $responser->redirect(uri: $this->getLinkUrl());
    }

    /**
     * Returns whether the entity is deletable.
     *
     * @param EntityInterface $entity
     * @return bool
     */
    public function isDeletable(EntityInterface $entity): bool
    {
        if (is_null($this->undeletable)) {
            return true;
        }
        
        if (is_array($this->undeletable)) {
            return in_array($entity->id(), $this->undeletable) ? false : true;
        }
        
        return call_user_func($this->undeletable, $entity) === true ? false : true;
    }
    
    /**
     * Sets the undeletable ids or using a callback returning whether the entity is undeletable.
     *
     * @param callable(EntityInterface):bool|array<array-key, int|string> $ids
     * @param callable|string $reason
     * @return static $this
     */
    public function undeletable(callable|array $ids, callable|string $reason = ''): static
    {
        $this->undeletable = $ids;
        $this->undeletableReason = $reason;
        return $this;
    }
    
    /**
     * Returns the undeletable reason.
     *
     * @param EntityInterface $entity
     * @return string
     */
    public function undeletableReason(EntityInterface $entity): string
    {
        if (is_string($this->undeletableReason)) {
            return $this->undeletableReason;
        }
        
        return ($this->undeletableReason)($entity);
    }
}