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
 * Store
 */
final class Store extends AbstractAction
{
    /**
     * Create a new Store.
     */
    public function __construct()
    {
        $this->route('{name}.store');
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
        return 'store';
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