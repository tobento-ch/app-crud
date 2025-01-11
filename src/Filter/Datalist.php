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
use Tobento\App\Crud\Filter;
use Tobento\Service\Collection\Arr;
use Tobento\Service\Iterable\Iter;
use Tobento\Service\Repository\Storage\StorageRepository;
use Tobento\Service\View\ViewInterface;

/**
 * Datalist
 */
class Datalist extends AbstractFilter
{
    /**
     * @var array
     */
    protected array $options = [];
    
    /**
     * @var array
     */
    protected null|array $optionsFromField = null;
    
    /**
     * Create a new Datalist.
     *
     * @param string $name
     */
    final public function __construct(
        protected string $name,
    ) {}
    
    /**
     * Create a new instance.
     *
     * @param string $name
     * @return static
     */
    public static function new(string $name): static
    {
        return new static($name);
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
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
        $repository = $action->controller()->repository();
        
        if (
            is_null($this->optionsFromField)
            || ! $repository instanceof StorageRepository
        ) {
            return;
        }
        
        [$fieldName, $fromInput, $limit] = $this->optionsFromField;
        
        // handle value:
        $value = null;
        
        if (is_string($fromInput)) {
            $value = $input->get($fromInput);
            
            if (empty($value) || !is_string($value)) {
                return;
            }
        }
        
        // handle translatable field
        $field = $action->fields()->get(name: $fieldName);
        
        if ($field?->isTranslatable()) {
            $fieldName = $fieldName.'->'.$action->getLocale();
        }
        
        // query:
        $query = $repository->query();
        
        if (is_string($value)) {
            $query->where($fieldName, 'like', $value.'%');
        }
                
        $this->options = $query->limit(number: $limit)
              ->column($fieldName)
              ->all();
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
        
        $html = '<div data-filter="'.$view->esc($this->name()).'" class="display-none">';
        $html .= $form->datalist(
            name: $this->name(),
            items: $this->options,
            attributes: [],
        );
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Sets the options.
     *
     * @param iterable|callable $options
     * @return static $this
     */
    public function options(iterable|callable $options): static
    {
        if (is_iterable($options)) {
            $this->options = Iter::toArray(iterable: $options);
            return $this;
        }
        
        $this->before(new Resolve($options, function(mixed $resolved): void {
            if (is_iterable($resolved)) {
                $this->options = Iter::toArray(iterable: $resolved);
            }
        }));
        
        return $this;
    }
    
    /**
     * Sets the options from field.
     *
     * @param string $field
     * @param null|string $fromInput
     * @param int $limit
     * @return static $this
     */
    public function optionsFromField(string $field, null|string $fromInput = null, int $limit = 50): static
    {
        $this->optionsFromField = [$field, $fromInput, $limit];
        return $this;
    }
}