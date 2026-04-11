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
use Tobento\App\Crud\Button\ButtonsInterface;
use Tobento\App\Crud\Button\Buttons;
use Tobento\App\Crud\Button;
use Tobento\App\Crud\Entity\Entities;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\FilterProcessorInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;

final class Index extends AbstractAction
{
    use Traits\InteractsWithRequest;
    
    /**
     * Create a new Index.
     *
     * @param null|string|Closure $title
     */
    public function __construct(
        null|string|Closure $title = null,
    ) {
        $this->title = $title;
        $this->route('{name}.index');
        $this->linkToAction('index');
        $this->view('crud/index');
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'index';
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
     * @param RequesterInterface $requester
     * @param ActionProcessorInterface $actionProcessor
     * @param FilterProcessorInterface $filterProcessor
     * @param ResponserInterface $responser
     * @return ResponseInterface
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function handle(
        RequesterInterface $requester,
        ActionProcessorInterface $actionProcessor,
        FilterProcessorInterface $filterProcessor,
        ResponserInterface $responser,
    ): ResponseInterface {
        $actions = $this->actions();
        $controller = $this->controller();
        
        $actionProcessor->preprocessAction(action: $this);
        
        $this->setInput($this->fetchInput(requester: $requester, action: $this));
        
        // Set the configured fields if none specified:
        if ($this->fields()->empty()) {
            $this->setFields($controller->getConfiguredFields(action: $this));
        }
        
        // Handle filters:
        if ($this->filters()->empty()) {
            $this->setFilters($controller->getConfiguredFilters($this));
        }

        $this->setFilters($filterProcessor->processFilters(filters: $this->filters(), action: $this));
        
        // Handle Entities:
        $entities = new Entities($controller->findEntities($this->filters()));
        
        $entities = $entities->map(function(object $item) use ($controller): EntityInterface {
            return $controller->createEntityFromObject($item);
        });

        $this->setEntities($entities);
        
        // Process action:
        $controller->isActionProcessable($this);
        $actionProcessor->processAction(action: $this);
        
        // Bulks:
        $bulkActions = $actions->bulks();
        
        foreach($bulkActions as $bulkAction) {
            $bulkAction->setActions($actions);
            $bulkAction->setActionProcessor($actionProcessor);
        }
        
        return $responser->render(
            view: $this->getView(),
            data: [
                'action' => $this->setFields($this->fields()->parent(null)),
                'buttons' => $this->buttons(),
                'filters' => $this->filters(),
                'bulkActions' => $bulkActions,
                'locale' => 'en',
            ],
        );
    }
    
    /**
     * Returns the buttons.
     *
     * @return ButtonsInterface
     */
    public function buttons(): ButtonsInterface
    {
        if ($this->buttons instanceof ButtonsInterface) {
            return $this->buttons;
        }
        
        $this->buttons = new Buttons(
            new Button\Link(label: $this->trans('Create New'), group: 'global')
                ->name('create')
                ->linkToAction('create'),
            new Button\Link(label: $this->trans('Edit'), group: 'entity')
                ->name('edit')
                ->raw()
                ->linkToAction('edit'),
            new Button\Link(label: $this->trans('Copy'), group: 'entity')
                ->name('copy')
                ->raw()
                ->linkToAction('copy'),
            new Button\Link(label: $this->trans('Show'), group: 'entity')
                ->name('show')
                ->raw()
                ->linkToAction('show'),
            new Button\Link(label: $this->trans('Data'), group: 'entity')
                ->name('show.json')
                ->linkToAction('show.json')
                ->raw()
                ->attr('target', '_blank'),
            new Button\Delete(label: $this->trans('Delete'), group: 'entity')
                ->name('delete')
                ->raw()
                ->askConfirmation()
                ->ajaxAction()
                ->linkToAction('delete'),
            // required for table editing columns:
            new Button\Link(label: 'Update', group: 'invisible')
                ->name('update')
                ->linkToAction('update'),
        );

        return $this->applyButtonsConfig($this->buttons);
    }
}