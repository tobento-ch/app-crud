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

use Throwable;
use Psr\Http\Message\ResponseInterface;
use Tobento\App\Crud\Action\ActionInterface;
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

final class BulkDelete extends AbstractAction implements BulkActionInterface
{
    use HasActionProcessor;
    use Traits\HandleBulk;
    use Traits\InteractsWithRequest;
    use Traits\ConfiguresModal;
    
    /**
     * Create a new BulkDelete.
     *
     * @param null|string $title
     */
    public function __construct(
        null|string $title = null,
    ) {
        $this->title = $title ?: 'Delete';
        $this->route('{name}.bulk', function(): array {
            return ['name' => $this->name()];
        });
        
        $this->linkToAction('index');
        $this->view('crud/bulk/modal');
        $this->modalButtonLabel(trans('Delete'));
    }
    
    /**
     * Returns the name. Must be sluggable and only of [a-z-] characters.
     *
     * @return string
     */
    public function name(): string
    {
        return 'bulk-delete';
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
     * @param RequesterInterface $requester
     * @param FilterProcessorInterface $filterProcessor
     * @param ResponserInterface $responser
     * @return void
     * @throws ActionProcessException
     * @psalm-suppress RedundantCondition
     * @psalm-suppress NoValue
     */
    public function processBulk(
        RequesterInterface $requester,
        FilterProcessorInterface $filterProcessor,
        ResponserInterface $responser
    ): void {
        // Process action for validation e.g.
        $storeAction = new Action\Store();
        $storeAction->setController($this->controller());
        $this->actionProcessor()->preprocessAction(action: $storeAction);
        
        $storeAction->setInput($this->fetchInput(requester: $requester, action: $storeAction, fresh: true));
        
        $fields = Fields::fromIterable($this->configureFields($storeAction));
        $storeAction->setFields($fields);
        
        $this->actionProcessor()->processFields(action: $storeAction, entity: new Entity());
        
        // Get input data
        $input = $storeAction->getInput();
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
        
        $repository = $this->controller()->repository();
        $deleteAction = $this->actions()->get('delete');
        
        if (! $deleteAction instanceof Action\Delete) {
            throw new ActionNotFoundException(actionName: 'delete');
        }
        
        $deleteAction->setFields($this->fields());
        
        $deletedCount = 0;
        
        foreach(array_values($ids) as $id) {
            if (!is_string($id) && !is_int($id)) {
                continue;
            }
            
            $entity = $repository->findById(id: $id);
            
            if (is_object($entity)) {
                $entity = $this->controller()->createEntityFromObject($entity);
                $deleteAction->setEntity($entity);
            } else {
                continue;
            }
            
            // Check if action is processable:
            try {
                $this->controller()->isActionProcessable($deleteAction);
            } catch (Throwable $e) {
                $responser->messages()->add(
                    level: 'error',
                    message: $e->getMessage(),
                );
                
                continue;
            }
            
            $this->actionProcessor()->processFields(action: $deleteAction, entity: $entity);
            
            // Delete entity:
            $this->controller()->deleteEntity(id: $id, entity: $deleteAction->entity());
            
            // Process deleted fields action:
            $this->actionProcessor()->processFieldsAction(
                action: $deleteAction,
                actionName: 'deleted',
            );
            
            $deletedCount++;
        }
        
        if ($deletedCount > 0) {
            $responser->messages()->add(
                level: 'success',
                message: trans(':count record(s) have been deleted.', [':count' => $deletedCount]),
            );
        } else {
            $responser->messages()->add(
                level: 'info',
                message: trans('No records were deleted.'),
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
        $fields = Fields::fromIterable($this->configureFields($createAction));
        
        $createAction->setFields($fields);
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
     * Returns whether to display the button to perform the action.
     *
     * @return bool
     */
    public function displayButton(): bool
    {
        return true;
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
        yield new Field\Select(name: $this->fieldName('selection_mode'), label: trans('Records to Delete'))
            ->group(trans('Options'))
            ->options([
                'ids' => trans('Selected Records'),
                'filtered' => trans('All Filtered Records'),
            ])
            ->infoText(new Message(
                title: trans('Are you sure you want to delete these items?'),
                warning: true,
                attributes: ['class' => 'mt-s'],
            ));
    }
}