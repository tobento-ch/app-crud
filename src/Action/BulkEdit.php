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
use Throwable;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Exception\ActionNotFoundException;
use Tobento\App\Crud\Exception\ActionProcessException;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\FilterProcessorInterface;
use Tobento\App\Crud\Html\Message;
use Tobento\App\Crud\Input\Input;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\View\ViewInterface;
use function Tobento\App\Translation\trans;

final class BulkEdit extends AbstractAction implements BulkActionInterface
{
    use HasActionProcessor;
    use Traits\HandleBulk;
    use Traits\InteractsWithRequest;
    use Traits\ConfiguresModal;
    
    /**
     * @var array<array-key, string>
     */
    private array $fieldNames = [];
    
    /**
     * Create a new BulkEdit.
     *
     * @param string $name Must be sluggable and only of [a-z-] characters.
     * @param null|string $title
     * @param string $fieldsFrom Selects which action's field definitions are used
     *   for building the bulk‑edit form. Allowed values:
     *   - 'index'  → use index action fields
     *   - 'create' → use create action fields (slightly heavier to process)
     */
    public function __construct(
        protected string $name,
        null|string $title = null,
        protected string $fieldsFrom = 'index',
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
        
        $this->view('crud/bulk/modal');
        $this->modalButtonLabel(trans('Apply'));
        $this->modalPosition('top');
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
     * Returns a namespaced field name for this action.
     *
     * @param string $suffix Field-specific suffix.
     * @return string
     */
    public function fieldName(string $suffix): string
    {
        return $this->name() . '_' . $suffix;
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
        return $this->handleBulk(
            action: $this,
            actionProcessor: $actionProcessor,
            requester: $requester,
            responser: $responser,
        );
    }
    
    /**
     * Sets the field(s).
     *
     * @param string ...$names
     * @return static $this
     */
    public function field(string ...$names): static
    {
        $this->fieldNames = $names;
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
     * Defines which action's field definitions should be used
     * when building the bulk‑edit form.
     *
     * Allowed values:
     *   - 'index'  Use fields from the index action (default)
     *   - 'create' Use fields from the create action
     *
     * @param string $action
     * @return static
     */
    public function fieldsFrom(string $action): static
    {
        $this->fieldsFrom = $action;
        return $this;
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
     * @param FilterProcessorInterface $filterProcessor
     * @param ResponserInterface $responser
     * @return void
     * @throws ActionProcessException
     * @psalm-suppress RedundantCondition
     * @psalm-suppress NoValue
     */
    public function processBulk(
        FilterProcessorInterface $filterProcessor,
        ResponserInterface $responser
    ): void {
        $input = $this->getInput();
        $repository = $this->controller()->repository();
        $updateAction = $this->actions()->get('update');
        
        if (! $updateAction instanceof Action\Update) {
            throw new ActionNotFoundException(actionName: 'update');
        }
        
        $fields = $this->fields()->filter(fn (FieldInterface $f): bool => in_array($f->name(), $this->fieldNames));
        
        $updateAction->setFields($fields);
        $updateAction->setInput($input);

        $attributes = $input->collection()
            ->onlyPresent($this->fields()->getNames())
            ->all();

        if (empty($attributes)) {
            return;
        }
        
        // handle mode:
        $selectionMode = $input->get($this->fieldName('selection_mode'), 'ids'); // or filtered

        // Ids selection mode
        if ($selectionMode === 'ids') {
            $ids = $input->get('ids', []);
        } else {
            // filtered selection mode
            // Get Index action for filters
            $indexAction = $this->actions()->get('index');

            if (is_null($indexAction)) {
                throw new ActionNotFoundException(actionName: 'index');
            }

            $indexAction->setFields($this->controller()->getConfiguredFields(action: $indexAction));

            // Handle filters:
            $filters = $this->controller()->getConfiguredFilters($indexAction);
            $filters = $filterProcessor->processFilters(filters: $filters, action: $indexAction);

            $ids = $this->controller()->repository()->findColumn(
                column: $this->controller()->entityIdName(),
                where: $filters->getWhereParameters(),
                orderBy: $filters->getOrderByParameters(),
            );
        }
        
        $updatedCount = 0;
        
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
            
            $updatedCount++;
        }
        
        if ($updatedCount > 0) {
            $responser->messages()->add(
                level: 'success',
                message: trans(':count record(s) have been updated.', [':count' => $updatedCount]),
            );
        } else {
            $responser->messages()->add(
                level: 'info',
                message: trans('No records were updated.'),
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
        $indexAction = $this->actions()->get('index');
        
        if (is_null($indexAction)) {
            return '';
        }
        
        $createAction = new Action\Create();
        $createAction->setController($indexAction->controller());
        $createAction->setActions($this->actions());
        
        // Determine source of fields
        if ($this->fieldsFrom === 'create') {
            // Use fields directly from create action
            $fields = $indexAction->controller()
                ->getConfiguredFields($createAction)
                ->filter(fn (FieldInterface $f): bool => in_array($f->name(), $this->fieldNames));
        } else {
            // Default: use index action field definitions
            $fields = $indexAction
                ->fields()
                ->filter(fn (FieldInterface $f): bool => in_array($f->name(), $this->fieldNames));         
        }

        // Restore input
        $requester = $this->container()->get(RequesterInterface::class);
        $createAction->setInput($this->fetchInput(requester: $requester, action: $this, fresh: false));
        
        // Merge and set fields
        $createAction->setFields(Fields::merge(
            primary: $fields,
            secondary: $this->configureFields($createAction),
        ));

        $this->actionProcessor->processFields(action: $createAction, entity: new Entity());
        $this->setFields($createAction->fields());
        
        return $view->render(
            view: $this->getView(),
            data: [
                'action' => $this,
            ],
        );
    }
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     * @psalm-suppress UnusedParam
     */
    protected function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        yield new Field\Select(
            name: $this->fieldName('selection_mode'),
            label: trans('Records to Edit')
        )
            ->group(trans('Options'))
            ->options([
                'ids' => trans('Selected Records'),
                'filtered' => trans('All Filtered Records'),
            ])
            ->infoText(new Message(
                title: trans('This will update multiple records.'),
                warning: true,
                attributes: ['class' => 'mt-s'],
            ));
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
}