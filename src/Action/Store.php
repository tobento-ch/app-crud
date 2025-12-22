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
use Tobento\App\Crud\Exception\ValidationException;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\InteractsWithRequestTrait;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Validation\ValidatorInterface;

final class Store extends AbstractAction
{
    use InteractsWithRequestTrait;
    use Traits\HandleNextAction;
    
    /**
     * Create a new Store.
     */
    public function __construct()
    {
        $this->route('{name}.store');
        $this->linkToAction('index');
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'store';
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
        $actions = $this->actions();
        $controller = $this->controller();
        
        $actionProcessor->preprocessAction(action: $this);
        
        // Handle input:
        $this->setInput(new Input(
            array_replace_recursive($requester->input()->all(), $requester->request()->getUploadedFiles())
        ));
        
        // Set the configured fields if none specified:
        if ($this->fields()->empty()) {
            $this->setFields($controller->getConfiguredFields(action: $this));
        }
        
        $fields = $this->fields()->creatable();
        
        if ($this->isLiveRequest($requester)) {
            $fields = $this->filterRequestedFieldsOnly($requester, $fields);
        }
        
        $this->setFields($fields);
        
        // Process action:
        $controller->isActionProcessable($this);
        $actionProcessor->processAction(action: $this);
        
        if ($this->isLiveRequest($requester)) {
            $response = $controller->create(
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
        $attributes = $this->getInput()
            ->collection()
            ->onlyPresent($this->fields()->storable()->getNames())
            ->all();
        
        $entity = $controller->storeEntity($attributes);
        $entity = $controller->createEntityFromObject($entity);
        
        // Process stored fields action:
        $actionProcessor->processFieldsAction(
            action: $this,
            actionName: 'stored',
            entity: $entity,
        );
        
        // Handle next action:
        $this->handleNextAction($this, $actions, $entity, $actionProcessor);
        
        // Return the response:
        return $responser->redirect(uri: $this->getLinkUrl());
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