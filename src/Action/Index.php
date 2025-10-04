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
 * Index
 */
final class Index extends AbstractAction
{
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