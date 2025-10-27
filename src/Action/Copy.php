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
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\Button\ButtonsInterface;
use Tobento\App\Crud\Button\Buttons;
use Tobento\App\Crud\Button;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Field\LiveAwareInterface;
use Tobento\App\Crud\InteractsWithRequestTrait;
use Tobento\Service\Requester\RequesterInterface;

final class Copy extends AbstractAction
{
    use InteractsWithRequestTrait;
    
    /**
     * Create a new Copy.
     *
     * @param null|string|Closure $title
     */
    public function __construct(
        null|string|Closure $title = null,
    ) {
        $this->title = $title;
        $this->route('{name}.copy', function(EntityInterface $entity): array {
            return ['id' => $entity->id()];
        });
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
        return 'copy';
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