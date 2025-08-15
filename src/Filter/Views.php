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
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\Uri\UriQuery;
use Tobento\Service\View\ViewInterface;

/**
 * Views filter to switch between views.
 */
class Views extends AbstractFilter
{
    /**
     * @var array<string, array<string, string>>
     */
    protected array $views = [];
    
    /**
     * @var string
     */
    protected string $viewId = '';
    
    /**
     * @var bool
     */
    protected bool $viewChanged = false;
    
    /**
     * Create a new Views instance.
     *
     * @param string $name
     */
    final public function __construct(
        protected string $name,
    ) {
        if ((bool) preg_match('/^[a-z-_.]+$/u', $name) === false) {
            throw new \InvalidArgumentException(
                sprintf('The name %s must only contain [a-z-_.] characters', $name)
            );
        }
    }

    /**
     * Create a new instance.
     *
     * @param string $name
     * @return static
     */
    public static function new(string $name = 'views'): static
    {
        return new static($name);
    }

    /**
     * Returns the view id.
     *
     * @return string
     */
    public function viewId(): string
    {
        return $this->viewId;
    }
    
    /**
     * Returns the whether the view has been changed.
     *
     * @return bool
     */
    public function viewChanged(): bool
    {
        return $this->viewChanged;
    }
    
    /**
     * Sets the default view to be displayed.
     *
     * @param string $id
     * @return static
     */
    public function defaultView(string $id): static
    {
        $this->viewId = $id;
        
        return $this;
    }
    
    /**
     * Adds a view.
     *
     * @param string $id
     * @param string $view
     * @param string $label
     * @return static
     */
    public function addView(string $id, string $view, string $label): static
    {
        $this->views[$id] = ['view' => $view, 'label' => $label];
        
        return $this;
    }
    
    /**
     * Returns the views.
     *
     * @return array<string, array<string, string>>
     */
    public function views(): array
    {
        return $this->views;
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
        if (! $input->has($this->name())) {
            if (isset($this->views[$this->viewId])) {
                $data = $this->views[$this->viewId];
                $action->view($data['view'] ?? 'crud/index');
            }
            
            return;
        }
        
        $viewId = $input->get($this->name());
        
        if (!is_string($viewId) || !array_key_exists($viewId, $this->views)) {
            return;
        }
        
        $data = $this->views[$viewId];
        
        $this->viewId = $viewId;
        
        $action->view($data['view'] ?? 'crud/index');
        
        $prevViewId = $input->get($this->name().'-prev');

        $this->viewChanged = $prevViewId === $viewId ? false : true;
    }
    
    /**
     * Returns the applied parameters.
     *
     * @return array
     */
    public function getAppliedParameters(): array
    {
        if (empty($this->viewId())) {
            return [];
        }
        
        return [
            $this->name() => $this->viewId(),
            $this->name().'-prev' => $this->viewId(),
        ];
    }
    
    /**
     * Returns if the filter is active, otherwise false.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->viewId() !== '';
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
        $attributes = [];
        $attributes['id'] ??= $form->nameToId('filter.'.$this->name());
        
        $body = $form->radios(
            name: $form->nameToArray('filter.'.$this->name()),
            items: $form->each(items: $this->views(), callback: function($item, $key): array {
                return [$key, $item['label'] ?? null];
            }),
            selected: $this->viewId(),
            attributes: $attributes,
            labelAttributes: [],
            withInput: true,
            wrapClass: 'wrap-v',
        );

        return $view->render(
            view: $this->view,
            data: [
                'name' => $this->name(),
                'label' => $this->label,
                'labelFor' => $this->label ? $attributes['id'] : '',
                'body' => $body, // must be escaped!
                'description' => $this->description,
                'open' => $this->isOpen(),
                'filter' => $this,
            ],
        );
    }
}