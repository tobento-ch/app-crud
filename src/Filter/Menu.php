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
use Tobento\Service\Iterable\Iter;
use Tobento\Service\Menu\Menu as ServiceMenu;
use Tobento\Service\Menu\MenuInterface;
use Tobento\Service\Menu\ItemInterface;
use Tobento\Service\View\ViewInterface;

/**
 * Menu
 */
class Menu extends AbstractFilter
{
    /**
     * @var null|string The active menu item.
     */
    protected null|string $active = null;

    /**
     * @var null|MenuInterface
     */
    protected null|MenuInterface $menu = null;
    
    /**
     * @var string
     */
    protected string $comparison = '=';
    
    /**
     * @var array
     */
    protected array $validComparison = [
        '=', '!=', '>', '<', '>=', '<=', '<>', '<=>', 'like', 'not like', 'contains',
    ];
    
    /**
     * @var string
     */
    protected string $view = 'crud/filter/menu';
    
    /**
     * Create a new Select.
     *
     * @param string $name
     * @param null|string $field
     */
    final public function __construct(
        protected string $name,
        protected null|string $field = null,
    ) {
        if ((bool) preg_match('/^[a-z-_]+$/u', $name) === false) {
            throw new \InvalidArgumentException(
                sprintf('The name %s must only contain [a-z-_] characters', $name)
            );
        }
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
     * Returns the field name.
     *
     * @return string
     */
    public function fieldName(): string
    {
        return $this->field ?: '';
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
        $active = $input->get($this->name());
        $menu = $this->getMenu();
        
        if (is_string($active) && $menu->get($active)) {
            $this->active = $active;
        } else {
            $this->active = 'none';
        }
        
        // set the active menu:
        $menu->on($this->active, function(ItemInterface $item): ItemInterface {
            $item->itemTag()->class('active');

            if ($item->getTreeLevel() > 0) {
                $item->parentTag()?->class('active');
            }

            $item->tag()->class('active');
            return $item;
        });
        
        $menu->active($this->active);
        
        if ($this->active === 'none') {
            $this->active = null;
        }
    }
    
    /**
     * Returns the applied parameters.
     *
     * @return array
     */
    public function getAppliedParameters(): array
    {
        if (empty($this->getActive())) {
            return [];
        }
        
        return [
            $this->name() => $this->getActive(),
        ];
    }

    /**
     * Returns the where parameters.
     *
     * @return array
     */
    public function getWhereParameters(): array
    {
        if (is_array($this->whereParameters)) {
            return $this->whereParameters;
        }
        
        if (empty($this->getActive()) || empty($this->fieldName())) {
            return [];
        }
        
        $value = (string)$this->getActive();
        
        $searchValue = match ($this->comparison) {
            'like' => '%'.$value.'%',
            'not like' => '%'.$value.'%',
            default => $value,
        };
        
        return [
            $this->fieldName() => [$this->comparison => $searchValue],
        ];
    }
    
    /**
     * Returns if the filter is active, otherwise false.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return empty($this->getActive()) || empty($this->fieldName()) ? false : true;
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
        
        return $view->render(
            view: $this->view,
            data: [
                'name' => $this->name(),
                'label' => $this->label,
                'labelFor' => $this->label ? $form->nameToId('filter.'.$this->name()) : '',
                'body' => '', // must be escaped!
                'description' => $this->description,
                'open' => $this->isOpen(),
                'filter' => $this,
                'menu' => $this->getMenu(),
            ],
        );
    }
    
    /**
     * Returns the active.
     *
     * @return null|string
     */
    public function getActive(): null|string
    {
        return $this->active;
    }
    
    /**
     * Returns the menu.
     *
     * @return MenuInterface
     */
    public function getMenu(): MenuInterface
    {
        return $this->menu ?: $this->createMenuFromItems(items: []);
    }
    
    /**
     * Sets the comparison.
     *
     * @param string $comparison
     * @return static $this
     */
    public function comparison(string $comparison): static
    {
        if (in_array($comparison, $this->validComparison)) {
            $this->comparison = $comparison;
        }
        
        return $this;
    }
    
    /**
     * Returns the comparison.
     *
     * @return string
     */
    public function getComparison(): string
    {
        return $this->comparison;
    }
    
    /**
     * Sets the menu items.
     *
     * @param iterable|callable $items
     * @return static $this
     */
    public function items(iterable|callable $items): static
    {
        if (is_iterable($items)) {
            $this->menu = $this->createMenuFromItems($items);
            return $this;
        }
        
        $this->before(new Resolve($items, function(iterable $resolved): void {
            $this->menu = $this->createMenuFromItems($resolved);
        }));
        
        return $this;
    }
    
    /**
     * Creates the menu from items.
     *
     * @param iterable $items
     * @return MenuInterface
     */
    protected function createMenuFromItems(iterable $items): MenuInterface
    {
        $items = Iter::toArray(iterable: $items);
        
        $menu = new ServiceMenu(name: 'default');
        $menu->link('?filter['.$this->name().']=none', '---')->id('none');
        
        foreach($items as $item) {
            $this->itemToMenu($item, $menu);
        }
        
        return $menu;
    }
    
    /**
     * Adds the item to the menu.
     *
     * @param mixed $item
     * @return void
     */
    protected function itemToMenu(mixed $item, MenuInterface $menu): void
    {
        if (!is_array($item)) {
            return;
        }
        
        $id = $item['id'] ?? null;
        $name = $item['name'] ?? null;
        $parent = $item['parent'] ?? null;
        
        if (!is_scalar($id) || !is_scalar($name)) {
            return;
        }
        
        $menuItem = $menu->link('?filter['.$this->name().']='.(string)$id, (string)$name)->id((string)$id);
        
        if (!empty($parent) && is_scalar($parent)) {
            $menuItem->parent((string)$parent);
        }
    }
}