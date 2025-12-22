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
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Field\LiveAwareInterface;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\InteractsWithRequestTrait;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;

final class Create extends AbstractAction
{
    use InteractsWithRequestTrait;
    
    /**
     * Create a new Create.
     *
     * @param null|string|Closure $title
     */
    public function __construct(
        protected null|string|Closure $title = null,
    ) {
        $this->title = $title;
        $this->route('{name}.create');
        $this->linkToAction('store');
        $this->view('crud/create');
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'create';
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
        $controller = $this->controller();
        
        $actionProcessor->preprocessAction(action: $this);
        
        // Handle entity:
        $this->setEntity(new Entity());

        // Handle input:
        $this->setInput(new Input($requester->input()->all()));
        
        // Set the configured fields if none specified:
        if ($this->fields()->empty()) {
            $this->setFields($controller->getConfiguredFields(action: $this));
        }

        $this->setFields($this->fields()->creatable());
        
        // Process action:
        $controller->isActionProcessable($this);
        $actionProcessor->processAction(action: $this);
        
        return $responser->render(
            view: $this->getView(),
            data: [
                'action' => $this->setFields($this->fields()->parent(null)),
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
            new Button\Link(label: $this->trans('Cancel'), group: 'entity')
                ->name('cancel')
                ->linkToAction('index'),
            new Button\Button(label: $this->trans('Save'), group: 'entity')
                ->name('save')
                ->attr(name: 'name', value: 'next_action')
                ->attr(name: 'value', value: 'edit')
                ->attr(name: 'data-loading', value: 'true')
                ->ajaxAction()
                ->primary(),
            new Button\Button(label: $this->trans('Save & Close'), group: 'entity')
                ->name('close')
                ->attr(name: 'data-loading', value: 'true')
                ->ajaxAction()
                ->primary(),
            new Button\Button(label: $this->trans('Save & Copy'), group: 'entity')
                ->name('copy')
                ->attr(name: 'name', value: 'next_action')
                ->attr(name: 'value', value: 'copy')
                ->attr(name: 'data-loading', value: 'true')
                ->ajaxAction()
                ->primary(),
            new Button\Button(label: $this->trans('Save & New'), group: 'entity')
                ->name('new')
                ->attr(name: 'name', value: 'next_action')
                ->attr(name: 'value', value: 'create')
                ->attr(name: 'data-loading', value: 'true')
                ->ajaxAction()
                ->primary(),
        );
        
        return $this->applyButtonsConfig($this->buttons);
    }
    
    /**
     * Returns the fields actions.
     *
     * @return array<string, callable>
     */
    public function getFieldsActions(): array
    {
        return [
            'afterLiveUpdate' => [$this, 'processAfterLiveUpdate'],
        ];
    }
    
    /**
     * Processes live update.
     *
     * @param ActionInterface $action
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @return void
     */
    public function processAfterLiveUpdate(
        ActionInterface $action,
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
    ): void {
        if (! $this->isLiveRequest($requester)) {
            return;
        }
        
        $fields = $this->filterRequestedFieldsOnly($requester, $action->fields());
        
        foreach($fields as $field) {
            if (! $field instanceof LiveAwareInterface) {
                continue;
            }
            
            if ($callable = $field->getAfterLiveHandler(action: $action->name())) {
                $actionProcessor->call($callable, [
                    'action' => $action,
                    'field' => $field,
                    'input' => $action->getInput(),
                ]);
            }
        }
    }
}