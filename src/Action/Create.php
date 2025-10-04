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

use Tobento\App\Crud\Button\ButtonsInterface;
use Tobento\App\Crud\Button\Buttons;
use Tobento\App\Crud\Button;
use Closure;

/**
 * Create
 */
final class Create extends AbstractAction
{
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
            Button\Link::new(label: $this->trans('Cancel'), group: 'entity')
                ->name('cancel')
                ->linkToAction('index'),
            Button\Button::new(label: $this->trans('Save'), group: 'entity')
                ->name('save')
                ->attr(name: 'name', value: 'next_action')
                ->attr(name: 'value', value: 'edit')
                ->attr(name: 'data-loading', value: 'true')
                ->ajaxAction()
                ->primary(),
            Button\Button::new(label: $this->trans('Save & Close'), group: 'entity')
                ->name('close')
                ->attr(name: 'data-loading', value: 'true')
                ->ajaxAction()
                ->primary(),
            Button\Button::new(label: $this->trans('Save & Copy'), group: 'entity')
                ->name('copy')
                ->attr(name: 'name', value: 'next_action')
                ->attr(name: 'value', value: 'copy')
                ->attr(name: 'data-loading', value: 'true')
                ->ajaxAction()
                ->primary(),
            Button\Button::new(label: $this->trans('Save & New'), group: 'entity')
                ->name('new')
                ->attr(name: 'name', value: 'next_action')
                ->attr(name: 'value', value: 'create')
                ->attr(name: 'data-loading', value: 'true')
                ->ajaxAction()
                ->primary(),
        );
        
        return $this->applyButtonsConfig($this->buttons);
    }
}