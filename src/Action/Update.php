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

use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Exception\ValidationException;
use Tobento\Service\Validation\ValidatorInterface;

/**
 * Update
 */
final class Update extends AbstractAction
{
    /**
     * @var null|callable|array<array-key, int|string>
     */
    private $unupdatable = null;
    
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
     * Create a new instance.
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
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
     * @param callable|array<array-key, int|string> $ids
     * @return static $this
     */
    public function unupdatable(callable|array $ids): static
    {
        $this->unupdatable = $ids;
        return $this;
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