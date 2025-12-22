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
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Exception\EntityNotFoundException;
use Tobento\App\Crud\Exception\ValidationException;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\InteractsWithRequestTrait;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Validation\ValidatorInterface;

final class Update extends AbstractAction
{
    use InteractsWithRequestTrait;
    use Traits\HandleNextAction;
    
    /**
     * @var null|callable(EntityInterface):bool|array<array-key, int|string>
     */
    private $unupdatable = null;
    
    /**
     * @var callable|string
     */
    private $unupdatableReason = '';
    
    /**
     * Create a new Update.
     */
    public function __construct()
    {
        $this->route('{name}.update', function(EntityInterface $entity): array {
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
        return 'update';
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
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function handle(
        int|string $id,
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        $actions = $this->actions();
        $controller = $this->controller();

        $actionProcessor->preprocessAction(action: $this);
        
        // Handle entity:
        $entity = $controller->repository()->findById($id);
        
        if ($entity === null) {
            throw new EntityNotFoundException($id, $this);
        }
        
        $this->setEntity($controller->createEntityFromObject($entity));
        
        // Handle input:
        $this->setInput(new Input(
            array_replace_recursive($requester->input()->all(), $requester->request()->getUploadedFiles())
        ));
        
        // Set the configured fields if none specified:
        if ($this->fields()->empty()) {
            $this->setFields($controller->getConfiguredFields(action: $this));
        }
        
        $fields = $this->fields()->editable();
        
        if ($requester->isAjax() || $this->isLiveRequest($requester)) {
            $fields = $this->filterRequestedFieldsOnly($requester, $fields);
        }

        $this->setFields($fields);
        
        // Process action:
        $controller->isActionProcessable($this);
        $actionProcessor->processAction(action: $this);

        if ($this->isLiveRequest($requester)) {
            $response = $controller->edit(
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
        $attributes = $this->getInput()
            ->collection()
            ->onlyPresent($this->fields()->storable()->getNames())
            ->all();
        
        $updatedItem = $controller->updateEntity($id, $attributes, $this->entity());
        $entity = $controller->createEntityFromObject($updatedItem);
        
        // Process updated fields action:
        $actionProcessor->processFieldsAction(
            action: $this,
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
        $this->handleNextAction($this, $actions, $entity, $actionProcessor);
        
        return $responser->redirect(uri: $this->getLinkUrl());
    }
    
    /**
     * Returns whether the entity is updatable.
     *
     * @param EntityInterface $entity
     * @return bool
     */
    public function isUpdatable(EntityInterface $entity): bool
    {
        if (is_null($this->unupdatable)) {
            return true;
        }
        
        if (is_array($this->unupdatable)) {
            return in_array($entity->id(), $this->unupdatable) ? false : true;
        }
        
        return call_user_func($this->unupdatable, $entity) === true ? false : true;
    }
    
    /**
     * Sets the unupdatable ids or using a callback returning whether the entity is unupdatable.
     *
     * @param callable(EntityInterface):bool|array<array-key, int|string> $ids
     * @param callable|string $reason
     * @return static $this
     */
    public function unupdatable(callable|array $ids, callable|string $reason = ''): static
    {
        $this->unupdatable = $ids;
        $this->unupdatableReason = $reason;
        return $this;
    }
    
    /**
     * Returns the unupdatable reason.
     *
     * @param EntityInterface $entity
     * @return string
     */
    public function unupdatableReason(EntityInterface $entity): string
    {
        if (is_string($this->unupdatableReason)) {
            return $this->unupdatableReason;
        }
        
        return ($this->unupdatableReason)($entity);
    }
    
    /**
     * Returns the fields actions.
     *
     * @return array<string, callable>
     */
    public function getFieldsActions(): array
    {
        return [
            'before' => [$this, 'processFieldsBefore'],
            'validation' => [$this, 'processFieldsValidation'],
        ];
    }
    
    /**
     * Processes the fields action before.
     *
     * @param ActionInterface $action
     * @param ActionProcessorInterface $actionProcessor
     * @return void
     */
    public function processFieldsBefore(ActionInterface $action, ActionProcessorInterface $actionProcessor): void
    {
        foreach($action->fields() as $field) {
            $callable = $field->getProcessor(action: $action->name().':before');
            
            if (is_callable($callable)) {
                $actionProcessor->call($callable, [
                    'action' => $action,
                    'field' => $field,
                    'input' => $action->getInput(),
                ]);
            }
        }
    }
    
    /**
     * Processes the fields validation.
     *
     * @param ValidatorInterface $validator
     * @return void
     * @throws ValidationException
     */
    public function processFieldsValidation(ValidatorInterface $validator): void
    {
        $rules = $this->fields()->getValidationRulesForAction($this->name());
        
        if (empty($rules)) {
            return;
        }
        
        $validation = $validator->validate(
            data: $this->getInput()->all(),
            rules: $rules,
        );
        
        if (!$validation->isValid()) {
            throw new ValidationException(
                validation: $validation,
                action: $this,
            );
        }
    }
}