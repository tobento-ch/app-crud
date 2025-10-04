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

namespace Tobento\App\Crud\Filter;

use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\Tag\Attributes;
use Tobento\Service\View\ViewInterface;

/**
 * Columns
 */
class Columns extends AbstractFilter
{
    /**
     * @var null|array
     */
    protected null|array $columns = null;
    
    /**
     * @var array<string, string>
     */
    protected array $fields = [];
    
    /**
     * @var null|string
     */
    protected null|string $actionsTitle = null;

    /**
     * @var array<array-key, string>
     */
    protected array $defaultColumns = [];
    
    /**
     * @var array<array-key, string>
     */
    protected array $reorderColumns = [];
    
    /**
     * @var bool
     */
    protected bool $sortable = true;

    /**
     * Create a new Columns.
     *
     * @param string ...$column
     */
    final public function __construct(string ...$column)
    {
        $this->columns = $column;
    }
    
    /**
     * Set the actions title.
     *
     * @param string $title
     * @return static $this
     */
    public function actionsTitle(string $title): static
    {
        $this->actionsTitle = $title;
        return $this;
    }
    
    /**
     * Sets the default columns to be displayed.
     *
     * @param string ...$column
     * @return static
     */
    public function default(string ...$column)
    {
        $this->defaultColumns = $column;
        
        if (empty($this->reorderColumns)) {
            $this->reorderColumns = $column;
        }
        
        return $this;
    }
    
    /**
     * Reorders the columns.
     *
     * @param string ...$column
     * @return static
     */
    public function reorder(string ...$column)
    {
        $this->reorderColumns = $column;
        return $this;
    }
    
    /**
     * Set whether columns can be resorted.
     *
     * @param bool $sortable
     * @return static
     */
    public function sortable(bool $sortable = true)
    {
        $this->sortable = $sortable;
        return $this;
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'columns';
    }
    
    /**
     * Returns the columns.
     *
     * @return array
     */
    public function columns(): array
    {
        return $this->columns ?: [];
    }
    
    /**
     * Applies the data to filter.
     *
     * @param InputInterface $input Might come from user input. So be careful.
     * @param FiltersInterface $filters
     * @param ActionInterface $action
     * @return void
     */
    public function apply(InputInterface $input, FiltersInterface $filters, ActionInterface $action): void
    {
        $this->columns = [];
        
        $columns = [];

        if (is_array($inputColumns = $input->get($this->name()))) {
            $inputColumns = array_filter($inputColumns, fn (mixed $v) => is_string($v));
            $this->reorder(...$inputColumns);
            $columns = $inputColumns;
        }
        
        $fields = $this->reorderFields($action->fields()->withParentFields($action));
        $action->setFields($fields);
        
        foreach($fields->withParentFields($action) as $field) {
            if ($field->isIndexable()) {
                $this->fields[$field->name()] = $field->label();
            }
        }
        
        $this->fields['actions'] = $this->actionsTitle ?: $action->trans('Actions');
        
        if (
            empty($columns)
            || (count($columns) === 1 && in_array('_none', $columns))
        ) {
            if (!empty($this->defaultColumns)) {
                $columns = $this->defaultColumns;
            } else {
                $columns = array_slice(array_keys($this->fields), 0, 5);
                $columns[] = 'actions';
            }
        }
        
        // verify and assign:
        foreach($columns as $column) {
            if (is_string($column) && array_key_exists($column, $this->fields)) {
                $this->columns[] = $column;
            }
        }
        
        $this->columns = array_unique($this->columns);
    }
    
    /**
     * Returns the applied parameters.
     *
     * @return array
     */
    public function getAppliedParameters(): array
    {
        return [
            $this->name() => $this->columns,
        ];
    }
    
    /**
     * Returns if the filter is active, otherwise false.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return empty($this->columns) ? false : true;
    }

    /**
     * Returns the rendered filter.
     *
     * @param ViewInterface $view
     * @return string
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function render(ViewInterface $view): string
    {
        if ($this->sortable) {
            $view->asset('assets/crud/filter-columns.js')->attr('type', 'module');
        }
        
        $form = $view->form();
        
        $attributes = new Attributes([
            'data-filter-sortable' => 'filter.'.$this->name().'.'.$this->getGroup(),
        ]);
        
        $body = '<div'.(string)$attributes.'>';
        
        $name = $form->nameToArray('filter.'.$this->name().'.');
        
        foreach($this->fields as $value => $label) {
            $id = $form->nameToId('filter.'.$this->name().'.'.$this->getGroup().'.'.$value);

            if ($value === 'actions' || !$this->sortable) {
                $body .= '<span class="wrap-v">';
            } else {
                $body .= '<span class="wrap-v drag-item">';
            }
            
            $body .= '<span>';
            $body .= $form->input(
                name: $name,
                type: 'checkbox',
                value: $value,
                attributes: ['id' => $id],
                selected: $this->columns ?: [],
            );
            $body .= $form->label(
                text: $label,
                for: $id,
            );
            $body .= '</span>';
            
            if ($value === 'actions' || !$this->sortable) {
                $body .= '';
            } else {
                $body .= '<span class="crud-drag link pr-xxs">'.$view->icon('grip-vertical')->parentAttr('class', 'display-flex').'</span>';
            }
            
            $body .= '</span>';
        }
        
        $body .= $form->input(
            name: $form->nameToArray('filter.'.$this->name()).'[]',
            type: 'hidden',
            value: '_none',
            attributes: ['id' => null],
        );
        
        $body .= '</div>';
        
        if (is_null($this->description)) {
            $this->description = $view->trans('The columns to display.');
        }
        
        return $view->render(
            view: $this->view,
            data: [
                'name' => $this->name(),
                'label' => $this->label,
                'labelFor' => '',
                'body' => $body, // must be escaped!
                'description' => $this->description,
                'open' => $this->isOpen(),
                'filter' => $this,
            ],
        );
    }
    
    /**
     * Reorders fields.
     *
     * @param FieldsInterface $fields
     * @return FieldsInterface
     */
    protected function reorderFields(FieldsInterface $fields): FieldsInterface
    {
        if (empty($this->reorderColumns)) {
            return $fields;
        }
        
        $fields = $fields->all();
        $columns = $this->reorderColumns;
        $ordered = [];
        
        foreach($columns as $column) {
            if (isset($fields[$column])) {
                $ordered[] = $fields[$column];
                unset($fields[$column]);
            }
        }
        
        foreach($fields as $field) {
            $ordered[] = $field;
        }

        return new Fields(...$ordered);
    }
}