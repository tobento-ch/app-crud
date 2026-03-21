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
use Tobento\App\Crud\Action;
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Exception\ActionNotFoundException;
use Tobento\App\Crud\Exception\ActionProcessException;
use Tobento\App\Crud\Exception\ValidationException;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\App\Crud\InteractsWithRequestTrait;
use Tobento\App\Crud\Validation\ManualValidation;
use Tobento\Service\Message\Messages;
use Tobento\Service\Message\MessagesInterface;
use Tobento\Service\Requester\Requester;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\View\ViewInterface;
use Throwable;

final class DynamicBulkEdit extends AbstractAction implements BulkActionInterface
{
    use InteractsWithRequestTrait;
    use HasActionProcessor;
    use Traits\HandleBulk;
    
    /**
     * @var array<string>|callable
     */
    protected $allowedFields = [];
    
    /**
     * @var null|callable
     */
    protected $inputAttributesModifier = null;
    
    /**
     * @var string
     */
    protected string $changeInputName = 'changes';
    
    /**
     * @var bool
     */
    protected bool $dynamicFieldsEnabled = true;
    
    /**
     * Create a new DynamicBulkEdit action.
     *
     * @param string $name Unique action name.
     * @param string $fieldLabel Label for the field selector.
     * @param string $valueLabel Label for the value input.
     * @param string $itemsGroupName Label for the group of items to update.
     * @param string $itemsAddText Label for the "add new field" button.
     * @param string|null $title Optional action title.
     */
    public function __construct(
        protected string $name,
        protected string $fieldLabel = 'Field',
        protected string $valueLabel = 'Value',
        protected string $itemsGroupName = 'Fields to update',
        protected string $itemsAddText = 'Add new field',
        null|string $title = null,
    ) {
        if ((bool) preg_match('/^[a-z-_.]+$/u', $name) === false) {
            throw new \InvalidArgumentException(
                sprintf('The name %s must only contain [a-z-] characters', $name)
            );
        }
        
        $this->title = $title ?: $name;
        $this->route('{name}.bulk', function(): array {
            return ['name' => $this->name()];
        });
        
        $this->linkToAction('index');
        
        $this->view('crud/bulk/edit');
    }
    
    /**
     * Returns the name. Must be sluggable and only of [a-z-] characters.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
    
    /**
     * Restrict / provide fields. Accepts list or callback.
     *
     * @param string|callable ...$fields
     * @return static $this
     */
    public function field(string|callable ...$fields): static
    {
        // If first arg is callable, treat as callback
        if (count($fields) === 1 && is_callable($fields[0])) {
            $this->allowedFields = $fields[0];
            return $this;
        }

        $this->allowedFields = $fields;
        return $this;
    }
    
    /**
     * Sets the fields.
     *
     * @param FieldsInterface $fields
     * @return static $this
     */
    public function setFields(FieldsInterface $fields): static
    {
        $this->fields = $fields;
        return $this;
    }
    
    /**
     * Modify input attributes.
     *
     * @param callable $modifier fn (array $attributes, Action\ActionInterface $action): array => $attributes;
     * @return static $this
     */
    public function modifyInputAttributes(callable $modifier): static
    {
        $this->inputAttributesModifier = $modifier;
        return $this;
    }

    /**
     * Returns the input attributes modifier.
     *
     * @return null|callable
     */
    public function inputAttributesModifier(): null|callable
    {
        return $this->inputAttributesModifier;
    }
    
    /**
     * Set the input name used for bulk‑edit change items.
     *
     * @param string $name
     * @return static
     */
    public function changeInputName(string $name): static
    {
        if ((bool) preg_match('/^[a-z-_]+$/u', $name) === false) {
            throw new \InvalidArgumentException(
                sprintf('The change input name "%s" may only contain [a-z-] characters', $name)
            );
        }
        
        $this->changeInputName = $name;
        return $this;
    }
    
    /**
     * Returns the change input name.
     *
     * @return string
     */
    public function getChangeInputName(): string
    {
        return $this->changeInputName;
    }
    
    /**
     * Enable or disable dynamic field generation for this bulk edit action.
     *
     * @param bool $dynamic
     * @return static
     */
    public function dynamicFields(bool $dynamic = true): static
    {
        $this->dynamicFieldsEnabled = $dynamic;
        return $this;
    }

    /**
     * Determine if dynamic fields are enabled.
     *
     * @return bool
     */
    public function dynamicFieldsEnabled(): bool
    {
        return $this->dynamicFieldsEnabled;
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
        $actionProcessor->preprocessAction(action: $this);
        
        // 1. Detect live request FIRST
        if ($this->isLiveRequest($requester)) {
            
            $controller = $this->controller();
            
            // // Ensure fields exist
            if ($this->fields()->empty()) {
                $this->setFields($controller->getConfiguredFields(action: $this));
            }

            $this->setFields($this->fields()->editable());

            // Always restore full input from requester
            // Restore full input from requester
            $this->setInput(new Input($requester->input()->all()));
            
            // Only process fields, do NOT process the action
            $actionProcessor->processFields(action: $this, entity: new Entity());
            
            $this->setActionProcessor($actionProcessor);
            
            // Render the modal
            return $responser->json([
                'status' => 200,
                'html'   => $this->render(view: $this->container()->get(ViewInterface::class)),
            ]);
        }

        // 2. Normal (non-live) request
        // Merge input for fields valdation e.g.
        $input = new Input($requester->input()->all());
        $attributes = $this->normalizeInputChanges($input);

        $requester = new Requester(
            $requester->request()->withParsedBody(array_merge($input->all(), $attributes)),
        );
        
        // Execute the bulk action and
        // remap validation errors to the correct input keys if needed.
        try {
            return $this->handleBulk(
                action: $this,
                actionProcessor: $actionProcessor,
                requester: $requester,
                responser: $responser,
            );
        } catch (ValidationException $e) {
            
            $mappedErrors = $this->remapValidationErrors($e->validation()->errors());
            
            $validation = new ManualValidation(
                errors: $mappedErrors,
                data: $e->validation()->data(),
                valid: $e->validation()->valid(),
                invalid: $e->validation()->invalid(),
                isValid: false,
            );

            throw new ValidationException(
                validation: $validation,
                action: $this,
                redirectActionName: $e->redirectActionName(),
                message: $e->getMessage(),
            );
        }
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
     * @param ResponserInterface $responser
     * @return void
     * @throws ActionProcessException
     * @psalm-suppress RedundantCondition
     * @psalm-suppress NoValue
     */
    public function processBulk(ResponserInterface $responser): void
    {
        $input = $this->getInput();
        $repository = $this->controller()->repository();
        $updateAction = $this->actions()->get('update');
        
        if (! $updateAction instanceof Action\Update) {
            throw new ActionNotFoundException(actionName: 'update');
        }
        
        // Get and filter allowed fields:
        $fields = $this->fields()->filter(
            fn (FieldInterface $f): bool => in_array($f->name(), $this->getAllowedFields(), true)
        );
        
        $updateAction->setFields($fields);
        $updateAction->setInput($input);
        
        $ids = $input->get('ids', []);
        
        $attributes = $input->collection()
            ->onlyPresent($fields->getNames())
            ->all();
        
        if (empty($attributes)) {
            return;
        }
        
        if ($this->inputAttributesModifier()) {
            $attributes = ($this->inputAttributesModifier())($attributes, $updateAction);
        }

        foreach(array_values($ids) as $id) {
            
            if (!is_string($id) && !is_int($id)) {
                continue;
            }
            
            $entity = $repository->findById(id: $id);

            if (is_null($entity)) {
                continue;
            }
            
            $entity = $this->controller()->createEntityFromObject($entity);
            $updateAction->setEntity($entity);

            // Check if action is processable:
            try {
                $this->controller()->isActionProcessable($updateAction);
            } catch (Throwable $e) {
                $responser->messages()->add(
                    level: 'error',
                    message: $e->getMessage(),
                );
                
                continue;
            }
            
            $this->actionProcessor()->processFields(action: $updateAction, entity: $entity);
            
            $updatedItem = $this->controller()->updateEntity($id, $attributes, $updateAction->entity());
            $entity = $this->controller()->createEntityFromObject($updatedItem);

            // Process updated fields action:
            $this->actionProcessor()->processFieldsAction(
                action: $updateAction,
                actionName: 'updated',
                entity: $entity,
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
        $view->asset('assets/crud/live.js')->attr('type', 'module');
        
        $indexAction = $this->actions()->get('index');
        $createAction = $this->actions()->get('create');
        
        if (is_null($indexAction) || is_null($createAction)) {
            return '';
        }

        // Restore input if available
        $input = $this->getInput();
        $createAction->setInput($input);
        
        if (empty($input->all())) {
            // Try to restore from request (old input after validation)
            $requester = $this->container()->get(RequesterInterface::class);
            $createAction->setInput(new Input($requester->input()->all()));
        }
        
        // Ensure fields exists  for dynamic field creation
        if ($this->fields()->empty()) {
            $controller = $this->controller();
            $fields = $controller->getConfiguredFields(action: $this);
            $this->setFields($fields);
        }
        
        $createAction->setFields($this->createFields());
        $this->actionProcessor->processFields(action: $createAction, entity: new Entity());
        $this->setFields($createAction->fields()->parent(null));
        
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
        return true;
    }
    
    /**
     * Returns the allowed fields, resolving callbacks when used.
     *
     * @return array<string>
     */
    protected function getAllowedFields(): array
    {
        if (is_callable($this->allowedFields)) {
            return $this->allowedFields = ($this->allowedFields)($this);
        }

        return $this->allowedFields;
    }
    
    /**
     * Create the dynamic fields used for bulk editing.
     * Call this from your controller before rendering, or hook into
     * wherever app-crud normally builds fields for actions.
     *
     * @return FieldsInterface
     */
    protected function createFields(): FieldsInterface
    {
        $allowed = $this->getAllowedFields();

        // If no allowed fields are defined, return an empty field set.
        // This results in an empty modal, prompting the developer to
        // explicitly configure allowed fields (e.g. to avoid exposing sensitive ones).
        if (empty($allowed)) {
            return new Fields();
        }
        
        // Build a single ItemsField with two sub-fields: field + value
        return new Fields(
            new Field\Items($this->getChangeInputName())
                // you may group the fields
                ->group($this->itemsGroupName) // set before defining fields!

                // define the fields per item:
                ->fields(
                    $this->buildFieldSelect($allowed),
                    $this->buildValueField($allowed),
                )

                // you may restrict items:
                ->validate('required|minItems:1|maxItems:20')

                // you may define the items number to be displayed on default:
                ->defaultItems(num: 1)

                // you may define a custom add text:
                ->addText($this->itemsAddText)
                
                ->onCreateField(function(FieldInterface $field, int $index, array $rowInput): FieldInterface {
                    // Example: rowInput = ['field' => 'status', 'value' => 'draft']
                    $selected = $rowInput['field'] ?? null;
                    
                    if (!$selected) {
                        return $field; // no change
                    }

                    // Only modify the "value" field, not the "field" select
                    if ($field->name() !== 'value') {
                        return $field;
                    }

                    // Now build the dynamic field based on the selected type
                    return $this->buildDynamicValueField($selected);
                })
            
                // you may not display the label text on create and edit action:
                ->withoutLabel(),
        );
    }
    
    /**
     * Normalize the submitted changes into ['field' => 'value', ...].
     *
     * @return array<string, mixed>
     */
    protected function normalizeInputChanges(InputInterface $input): array
    {
        $changes = $input->get($this->getChangeInputName(), []);
        $allowed = $this->getAllowedFields();
        $mapped  = [];

        foreach ($changes as $change) {
            if (!isset($change['field']) || !is_string($change['field'])) {
                continue;
            }

            $field = $change['field'];

            if (!empty($allowed) && !in_array($field, $allowed, true)) {
                continue;
            }

            $mapped[$field] = $change['value'] ?? null;
        }

        foreach ($mapped as $field => $value) {
            $mapped[$field] = $value;
        }
        
        return $mapped;
    }
    
    /**
     * Remaps validation error keys to their corresponding bulk‑edit input keys.
     *
     * @param MessagesInterface $errors
     * @return MessagesInterface
     */
    protected function remapValidationErrors(MessagesInterface $errors): MessagesInterface
    {
        $changes = $this->getInput()->get($this->getChangeInputName(), []);
        $map = $this->buildFieldIndexMap($changes);
        
        foreach ($errors->all() as $message) {
            $key = $message->key();

            if (!isset($map[$key])) {
                continue;
            }

            $index = $map[$key];

            $errors->addMessage($message->withKey(sprintf('%s.%s.value', $this->getChangeInputName(), $index)));
        }

        return $errors;
    }
    
    /**
     * Builds a map of field names to their corresponding change indexes.
     *
     * @param array $changes
     * @return array<string,int>
     */
    protected function buildFieldIndexMap(array $changes): array
    {
        $map = [];

        foreach ($changes as $index => $change) {
            if (!isset($change['field'])) {
                continue;
            }

            $field = $change['field'];
            $map[$field] = $index;
        }

        return $map;
    }
    
    /**
     * Build the "field" select.
     *
     * @param array<string> $allowed
     * @return FieldInterface
     */
    protected function buildFieldSelect(array $allowed): FieldInterface
    {
        $options = [];

        foreach ($allowed as $name) {
            $field = $this->fields()->get($name);
            $label = $field?->label() ?: ucfirst((string) $name);
            $options[$name] = $label;
        }

        $max = 20;
        $fields = [];

        for ($i = 1; $i <= $max; $i++) {
            $fields[] = sprintf('%s.%d.value', $this->getChangeInputName(), $i);
        }

        return new Field\Select(name: 'field', label: $this->fieldLabel)
            ->options($options)
            ->validate('required')
            ->live(
                fields: $fields,
            );
    }

    /**
     * Build the "value" field.
     * You can make this type-aware by looking up configured CRUD fields.
     *
     * @param array<string> $allowed
     * @return FieldInterface
     */
    protected function buildValueField(array $allowed): FieldInterface
    {
        $default = $allowed[0] ?? 'text';

        return $this->buildDynamicValueField($default);
    }
    
    /**
     * Builds the dynamic value field for the selected field name.
     *
     * @param string $selected The selected field name.
     * @return FieldInterface
     */
    protected function buildDynamicValueField(string $selected): FieldInterface
    {
        $field = $this->fields()->get($selected);
        
        // Always fallback to Text if dynamic fields are disabled or field not found
        if (is_null($field) || ! $this->dynamicFieldsEnabled()) {
            return new Field\Text(name: 'value', label: $this->valueLabel);
        }

        return match ($field::class) {
            Field\Text::class => new Field\Text(name: 'value', label: $this->valueLabel),

            Field\Textarea::class => new Field\Textarea(name: 'value', label: $this->valueLabel),

            Field\Select::class => new Field\Select(name: 'value', label: $this->valueLabel)
                ->options($field->getOptions()),

            Field\Radios::class => new Field\Radios(name: 'value', label: $this->valueLabel)
                ->options($field->getOptions()),

            Field\Checkboxes::class => new Field\Checkboxes(name: 'value', label: $this->valueLabel)
                ->options($field->getOptions()),

            default => new Field\Text(name: 'value', label: $this->valueLabel),
        };
    }
}