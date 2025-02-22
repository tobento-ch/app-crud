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
     * Create a new Columns.
     *
     * @param string ...$column
     */
    final public function __construct(string ...$column)
    {
        $this->columns = $column;
    }

    /**
     * Create a new instance.
     *
     * @param string ...$column
     * @return static
     */
    public static function new(string ...$column): static
    {
        return new static(...$column);
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
        foreach($action->fields() as $field) {
            if ($field->isIndexable()) {
                $this->fields[$field->name()] = $field->label();
            }
        }
        
        $this->fields['actions'] = $this->actionsTitle ?: $action->trans('Actions');
        
        $columns = [];

        if (is_array($inputColumns = $input->get($this->name()))) {
            $columns = $inputColumns;
        }
        
        if (
            empty($columns)
            || (count($columns) === 1 && in_array('_none', $columns))
        ) {
            $columns = array_slice(array_keys($this->fields), 0, 5);
            $columns[] = 'actions';
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
            $this->description = $view->trans('The columns to display.');
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