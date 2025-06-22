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
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\View\ViewInterface;

/**
 * EditableColumns
 */
class EditableColumns extends AbstractFilter
{
    /**
     * @var array<array-key, string>
     */
    protected array $editableColumns = [];
    
    /**
     * @var null|array
     */
    protected null|array $columns = null;
    
    /**
     * @var array<string, string>
     */
    protected array $fields = [];

    /**
     * Create a new EditableColumns.
     *
     * @param string ...$editableColumn
     */
    final public function __construct(string ...$editableColumn)
    {
        $this->editableColumns = $editableColumn;
    }

    /**
     * Create a new instance.
     *
     * @param string ...$editableColumn
     * @return static
     */
    public static function new(string ...$editableColumn): static
    {
        return new static(...$editableColumn);
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'editable-columns';
    }

    /**
     * Returns the editable columns.
     *
     * @return array<array-key, string>
     */
    public function editableColumns(): array
    {
        return $this->editableColumns;
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
        foreach($action->fields()->withParentFields($action) as $field) {
            if (in_array($field->name(), $this->editableColumns())) {
                $this->fields[$field->name()] = $field->label();
            }
        }
        
        $columns = [];

        if (is_array($inputColumns = $input->get($this->name()))) {
            $columns = $inputColumns;
        }

        // verify and assign:
        foreach($columns as $column) {
            if (is_string($column) && array_key_exists($column, $this->fields)) {
                $this->columns[] = $column;
            }
        }
        
        // mark field as table editable:
        if (is_array($this->columns)) {
            foreach($action->fields()->withParentFields($action) as $field) {
                if (in_array($field->name(), $this->columns)) {
                    $field->tableEditable(true);
                }
            }
        }
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
        $form = $view->form();
        $idAttribute = $form->nameToId('filter.'.$this->name().'.'.$this->getGroup());
        $body = $form->checkboxes(
            name: $form->nameToArray('filter.'.$this->name()),
            items: $this->fields,
            selected: $this->columns ?: [],
            attributes: ['id' => $idAttribute],
            labelAttributes: [],
            withInput: true,
            wrapClass: 'wrap-v'
        );
        
        $body .= $form->input(
            name: $form->nameToArray('filter.'.$this->name()).'[]',
            type: 'hidden',
            value: '_none',
            attributes: ['id' => null],
        );
        
        if (is_null($this->description)) {
            $this->description = $view->trans('The columns to edit in table.');
        }
        
        return $view->render(
            view: $this->view,
            data: [
                'name' => $this->name(),
                'label' => $this->label,
                'labelFor' => $this->label ? $idAttribute : '',
                'body' => $body, // must be escaped!
                'description' => $this->description,
                'open' => $this->isOpen(),
                'filter' => $this,
            ],
        );
    }
}