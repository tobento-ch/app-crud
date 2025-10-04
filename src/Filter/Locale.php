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
use Tobento\Service\Collection\Arr;
use Tobento\Service\Iterable\Iter;
use Tobento\Service\View\ViewInterface;

/**
 * Locale
 */
class Locale extends AbstractFilter
{
    /**
     * @var array
     */
    protected array $locales = [];
    
    /**
     * @var string
     */
    protected string $locale = 'en';
    
    /**
     * Create a new Locale.
     *
     * @param string $name
     */
    final public function __construct(
        protected string $name = 'locale',
    ) {}
    
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
        $this->locale = $action->getLocale();
        $this->locales = $action->getLocales();
        
        if (!$input->has($this->name())) {
            return;
        }

        $locale = $input->get($this->name());
        
        if (!is_string($locale) || !array_key_exists($locale, $this->locales)) {
            return;
        }
        
        $this->locale = $locale;
        $locales = $this->locales;
        $localeName = $this->locales[$locale];
        unset($locales[$locale]);
        $action->locales([$locale => $localeName] + $locales);
    }
    
    /**
     * Returns the applied parameters.
     *
     * @return array
     */
    public function getAppliedParameters(): array
    {
        if (empty($this->getLocale())) {
            return [];
        }
        
        return [
            $this->name() => $this->getLocale(),
        ];
    }
    
    /**
     * Returns if the filter is active, otherwise false.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return !empty($this->getLocale());
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
        $attributes['id'] = $form->nameToId('filter.'.$this->name());
        
        $body = $form->radios(
            name: $form->nameToArray('filter.'.$this->name()),
            items: $this->getLocales(),
            selected: $this->getLocale(),
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
    
    /**
     * Returns the locales.
     *
     * @return array
     */
    public function getLocales(): array
    {
        return $this->locales;
    }
    
    /**
     * Returns the locale.
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }
}