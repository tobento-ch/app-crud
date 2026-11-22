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

namespace Tobento\App\Crud\Field;

use Tobento\App\Crud\Action\ActionInterface;
use Tobento\Service\Form\Form;
use Tobento\Service\Support\Str;
use Tobento\Service\Uri\BaseUriInterface;
use Tobento\Service\View\ViewInterface;

/**
 * Previews how the entity is shown as a search engine result,
 * updated live while editing the title, slug and description.
 */
class SearchEnginePreview extends AbstractField
{
    use Traits\Hidden;
    
    /**
     * @var array<int, string>
     */
    protected array $titleFrom = [];
    
    /**
     * @var array<int, string>
     */
    protected array $slugFrom = [];
    
    /**
     * @var array<int, string>
     */
    protected array $descriptionFrom = [];
    
    /**
     * @var string
     */
    protected string $uri = '{slug}';
    
    /**
     * Create a new SearchEnginePreview.
     *
     * @param string $name
     * @param null|string $label
     */
    final public function __construct(
        string $name,
        null|string $label = null,
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->process('index', [$this, 'processIndexAction']);
        $this->process('create|edit|copy|show', [$this, 'processRender']);
        $this->indexable(false);
        $this->storable(false);
        $this->showable(false);
        $this->configure();
    }
    
    /**
     * Sets the fields to get the title from. The first non-empty value is used.
     *
     * @param string ...$names
     * @return static $this
     */
    public function titleFrom(string ...$names): static
    {
        $this->titleFrom = $names;
        return $this;
    }
    
    /**
     * Returns the fields to get the title from.
     *
     * @return array<int, string>
     */
    public function getTitleFrom(): array
    {
        return $this->titleFrom;
    }
    
    /**
     * Sets the fields to get the slug from. The first non-empty value is used.
     *
     * @param string ...$names
     * @return static $this
     */
    public function slugFrom(string ...$names): static
    {
        $this->slugFrom = $names;
        return $this;
    }
    
    /**
     * Returns the fields to get the slug from.
     *
     * @return array<int, string>
     */
    public function getSlugFrom(): array
    {
        return $this->slugFrom;
    }
    
    /**
     * Sets the fields to get the description from. The first non-empty value is used.
     *
     * @param string ...$names
     * @return static $this
     */
    public function descriptionFrom(string ...$names): static
    {
        $this->descriptionFrom = $names;
        return $this;
    }
    
    /**
     * Returns the fields to get the description from.
     *
     * @return array<int, string>
     */
    public function getDescriptionFrom(): array
    {
        return $this->descriptionFrom;
    }
    
    /**
     * Sets the uri with the {slug} and {locale} placeholders.
     * A relative uri is resolved against the base uri.
     *
     * @param string $uri E.g. 'blog/{slug}' or 'https://example.com/{locale}/{slug}'
     * @return static $this
     */
    public function uri(string $uri): static
    {
        $this->uri = $uri;
        return $this;
    }
    
    /**
     * Returns the uri.
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }
    
    /**
     * Processes the create, edit and show action.
     *
     * @param ActionInterface $action
     * @param SearchEnginePreview $field
     * @param ViewInterface $view
     * @param BaseUriInterface $baseUri
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processRender(
        ActionInterface $action,
        SearchEnginePreview $field,
        ViewInterface $view,
        BaseUriInterface $baseUri,
    ): void {
        if ($field->isHidden()) {
            $field->html('');
            return;
        }
        
        $sources = [
            'title' => $this->resolveSources($action, $field->getTitleFrom()),
            'slug' => $this->resolveSources($action, $field->getSlugFrom()),
            'description' => $this->resolveSources($action, $field->getDescriptionFrom()),
        ];
        
        $translatable = false;
        
        foreach ($sources as $names) {
            foreach ($names as $name) {
                if (str_contains($name, '{locale}')) {
                    $translatable = true;
                }
            }
        }
        
        // the form provides the old input, e.g. after a validation error:
        $form = $view->form();
        $form = $form instanceof Form ? $form : null;
        
        $locales = $field->locales();
        $locale = array_key_first($locales) ?? $field->locale();
        $uri = $this->resolveUri($field->getUri(), $baseUri);
        
        // on show, a preview for each locale, as there are no inputs to switch the locale:
        $previewLocales = $translatable && $action->name() === 'show' && !empty($locales)
            ? array_keys($locales)
            : [$locale];
        
        $previews = [];
        
        foreach ($previewLocales as $previewLocale) {
            $slug = $this->getSourceValue($field, $sources['slug'], $previewLocale, $action, $form);
            
            $previews[] = [
                'localeName' => $locales[$previewLocale] ?? $previewLocale,
                'title' => $this->getSourceValue($field, $sources['title'], $previewLocale, $action, $form),
                'url' => strtr($uri, ['{locale}' => $previewLocale, '{slug}' => $slug]),
                'description' => $this->getSourceValue($field, $sources['description'], $previewLocale, $action, $form),
            ];
        }
        
        $attributes = $field->getAttributes();
        
        // the config for the javascript, not needed on show as there are no inputs:
        if ($action->name() !== 'show') {
            $attributes['data-search-engine-preview'] = [
                'title' => $sources['title'],
                'slug' => $sources['slug'],
                'description' => $sources['description'],
                'uri' => $uri,
                'locale' => $locale,
                'locales' => $translatable ? $locales : [],
            ];
        }
        
        $field->html($view->render(
            view: 'crud/field/search-engine-preview',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'translatable' => $translatable,
                'previews' => $previews,
                'attributes' => $attributes,
            ],
        ));
    }
    
    /**
     * Processes the index action.
     *
     * @param SearchEnginePreview $field
     * @param BaseUriInterface $baseUri
     * @return void
     */
    public function processIndexAction(SearchEnginePreview $field, BaseUriInterface $baseUri): void
    {
        if ($field->isHidden()) {
            $field->html('');
            return;
        }
        
        $locale = $field->locale();
        $title = $this->getSourceValue($field, $field->getTitleFrom(), $locale);
        $slug = $this->getSourceValue($field, $field->getSlugFrom(), $locale);
        $description = $this->getSourceValue($field, $field->getDescriptionFrom(), $locale);
        $url = strtr($this->resolveUri($field->getUri(), $baseUri), ['{locale}' => $locale, '{slug}' => $slug]);
        
        // no view rendering for speed, as rendered for each row:
        $field->html(
            '<div class="text-700">'.Str::esc($title).'</div>'
            .'<div class="text-success mt-xxs">'.Str::esc($url).'</div>'
            .'<div class="mt-xxs">'.Str::esc(mb_strimwidth($description, 0, 100, '...')).'</div>'
        );
    }
    
    /**
     * Returns the input names of the given fields, with a {locale} placeholder for translatable fields.
     *
     * @param ActionInterface $action
     * @param array<int, string> $names
     * @return array<int, string> E.g. ['meta_title.{locale}', 'title.{locale}']
     */
    protected function resolveSources(ActionInterface $action, array $names): array
    {
        $sources = [];
        
        foreach ($names as $name) {
            $field = $action->fields()->get($name);
            $sources[] = $field?->isTranslatable() ? $name.'.{locale}' : $name;
        }
        
        return $sources;
    }
    
    /**
     * Returns the first non-empty value of the given sources as plain text.
     * The input is used first: the action input, e.g. on a live update,
     * then the old input of the form, e.g. after a validation error.
     *
     * @param FieldInterface $field
     * @param array<int, string> $sources
     * @param string $locale
     * @param null|ActionInterface $action
     * @param null|Form $form
     * @return string
     */
    protected function getSourceValue(
        FieldInterface $field,
        array $sources,
        string $locale,
        null|ActionInterface $action = null,
        null|Form $form = null,
    ): string {
        foreach ($sources as $source) {
            // the locale is used if the value is translated:
            $value = $field->entity()->get(str_replace('.{locale}', '', $source), '', $locale);
            
            $name = str_replace('{locale}', $locale, $source);
            
            if ($action && $action->getInput()->has($name)) {
                $value = $action->getInput()->get($name);
            } elseif ($form) {
                $value = $form->getInput(name: $name, default: $value);
            }
            
            if (!is_string($value)) {
                continue;
            }
            
            // the value may be html, e.g. from a text editor:
            $value = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            
            if ($value !== '') {
                return $value;
            }
        }
        
        return '';
    }
    
    /**
     * Returns the uri resolved against the base uri if relative.
     *
     * @param string $uri
     * @param BaseUriInterface $baseUri
     * @return string
     */
    protected function resolveUri(string $uri, BaseUriInterface $baseUri): string
    {
        if (str_contains($uri, '://') || str_starts_with($uri, '//')) {
            return $uri;
        }
        
        return rtrim($baseUri->__toString(), '/').'/'.ltrim($uri, '/');
    }
}