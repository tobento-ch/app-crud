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
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Slugging\SlugRepositoryInterface;
use Tobento\Service\Support\Str;
use Tobento\Service\View\ViewInterface;
use Tobento\Service\Slugifier\Slug as SlugEntity;
use Tobento\Service\Slugifier\SlugifierFactory;
use Tobento\Service\Slugifier\SlugifierInterface;
use Tobento\Service\Slugifier\SlugifiersInterface;

/**
 * Slug
 */
class Slug extends AbstractField
{
    use Traits\HasValueFormatter;
    use Traits\Hidden;
    use Traits\PrefixSuffix;
    
    /**
     * @var null|string
     */
    protected null|string $fromField = null;
    
    /**
     * @var null|SlugifierInterface
     */
    protected null|SlugifierInterface $slugifier = null;
    
    /**
     * Create a new Slug.
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
        $this->process('create|edit|copy', [$this, 'processCreateEdit']);
        $this->process('store:before|update:before', [$this, 'processBeforeSave']);
        $this->process('store', [$this, 'processStore']);
        $this->process('update', [$this, 'processUpdate']);
        $this->process('show', [$this, 'processShow']);
        $this->uniqueSlugs(true);
        $this->slugifier('crud');
        $this->validate('string');
        $this->configure();
    }
    
    /**
     * Returns the value for the field.
     *
     * @param Slug $field
     * @param null|string $locale
     * @return string
     */
    public function getValue(Slug $field, null|string $locale = null): string
    {
        if (!is_null($locale)) {
            return $field->entity()->get($field->name(), $field->getDefaultValue($locale), $locale);
        }
        
        return $field->entity()->get($field->name(), $field->getDefaultValue());
    }
    
    /**
     * Returns the default value.
     *
     * @param null|string $locale
     * @return string
     */
    public function getDefaultValue(null|string $locale = null): string
    {
        return '';
    }
    
    /**
     * Sets if to use unique slugs.
     *
     * @param bool $unique
     * @return static $this
     */
    public function uniqueSlugs(bool $unique = true): static
    {
        if ($unique) {
            $this->process('stored|updated', [$this, 'processSaved']);
            $this->process('deleted', [$this, 'processDeleted']);
        } else {
            $this->process('stored|updated', null);
            $this->process('deleted', null);
        }

        return $this;
    }
    
    /**
     * Sets the field name from which to create the slug from.
     *
     * @param string $name
     * @return static $this
     */
    public function fromField(string $name): static
    {
        $this->fromField = $name;
        return $this;
    }
    
    /**
     * Returns the from field.
     *
     * @return null|string
     */
    public function getFromField(): null|string
    {
        return $this->fromField;
    }
    
    /**
     * Sets the field name from which to create the slug from.
     *
     * @param SlugifierInterface|string|callable $slugifier
     * @return static $this
     */
    public function slugifier(SlugifierInterface|string|callable $slugifier): static
    {
        if (is_string($slugifier)) {
            $slugifier = function(SlugifiersInterface $slugifiers) use ($slugifier): SlugifierInterface {
                return $slugifiers->get($slugifier);
            };
        }
        
        if ($slugifier instanceof SlugifierInterface) {
            $this->slugifier = $slugifier;
            return $this;
        }
        
        $this->resolve(
            resolve: $slugifier,
            resolved: function(Slug $field, mixed $resolved): void {
                if ($resolved instanceof SlugifierInterface && is_null($this->slugifier)) {
                    $field->slugifier($resolved);
                }
            },
            action: 'store|update',
        );
        
        return $this;
    }
    
    /**
     * Returns the slugifier.
     *
     * @return SlugifierInterface
     */
    public function getSlugifier(): SlugifierInterface
    {
        if (is_null($this->slugifier)) {
            $this->slugifier = (new SlugifierFactory())->createSlugifier();
        }
        
        return $this->slugifier;
    }
    
    /**
     * Pre processes the store and update action before validation
     * where we create the slug from the specified from field.
     *
     * @param ActionInterface $action
     * @param Slug $field
     * @param InputInterface $input
     * @return void
     */
    public function processBeforeSave(
        ActionInterface $action,
        Slug $field,
        InputInterface $input,
    ): void {
        if (! $input->has($field->name())) {
            return;
        }
        
        $value = $input->get($field->name());
        
        // handle untranslatable:
        if (! $field->isTranslatable()) {
            $slug = $this->slugify($value, $field->locale(), $action, $field, $input);
            $input->set($field->name(), $slug);
            return;
        }
        
        // handle translatable:
        if (is_string($value)) {
            $value = [$field->locale() => $value];
        }

        if (!is_array($value)) {
            $input->delete($field->name());
            return;
        }

        $data = $field->entity()->get($field->name(), []);
        
        foreach($value as $locale => $val) {
            if (!array_key_exists($locale, $field->locales())) {
                continue;
            }
            
            $data[$locale] = $this->slugify($val, $locale, $action, $field, $input);
        }
        
        $input->set($field->name(), $data);
    }
    
    /**
     * Slugify value.
     *
     * @param mixed $value
     * @param string $locale
     * @param ActionInterface $action
     * @param Slug $field
     * @param InputInterface $input
     * @return string The slugified value.
     */
    protected function slugify(
        mixed $value,
        string $locale,
        ActionInterface $action,
        Slug $field,
        InputInterface $input,
    ): string {
        if (!is_string($value)) {
            $value = '';
        }
        
        // get the value from the defined field:
        if ($value === '' && is_string($field->getFromField())) {
            $fromField = $action->fields()->get($field->getFromField());
            
            if (is_null($fromField)) {
                return ''; // use required validation rule to check.
            }
            
            $fieldName = $fromField->isTranslatable() ? $fromField->name().'.'.$locale : $fromField->name();
            
            $value = $input->get($fieldName, $fromField->entity()->get($fieldName, ''));
            
            if (!is_string($value) || $value === '') {
                return ''; // use required validation rule to check.
            }
        }
        
        // do not slugify if slug has not changed:
        $slugFieldName = $field->isTranslatable() ? $field->name().'.'.$locale : $field->name();
        $oldSlug = $field->entity()->get($slugFieldName, '');
        
        if ($oldSlug !== '' && $oldSlug === $value) {
            return $value;
        }
        
        // slugify value:
        return $field->getSlugifier()->slugify($value);
    }
    
    /**
     * Processes the create and edit action.
     *
     * @param ActionInterface $action
     * @param Slug $field
     * @param ViewInterface $view
     * @return void
     */
    public function processCreateEdit(
        ActionInterface $action,
        Slug $field,
        ViewInterface $view
    ): void {
        if ($field->isHidden()) {
            $field->html('');
            return;
        }
        
        $field->html($view->render(
            view: 'crud/field/text',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'inputType' => 'text',
                'inputAttributes' => $field->getAttributes(),
            ],
        ));
    }

    /**
     * Processes the stored and updated action.
     *
     * @param ActionInterface $action
     * @param Slug $field
     * @return void
     */
    public function processSaved(
        ActionInterface $action,
        Slug $field,
        SlugRepositoryInterface $slugRepository,
    ): void {
        $entity = $field->entity(); // stored or updated entity
        $oldEntity = $field->oldEntity(); // old entity before stored or updated
        $resourceKey = $action->controller()->resourceName();
        
        if (! $field->isTranslatable()) {
            if ($entity->get($field->name(), '') === $oldEntity->get($field->name(), '')) {
                return;
            }

            $slugRepository->deleteSlug(new SlugEntity(
                slug: $oldEntity->get($field->name(), ''),
                locale: 'en',
                resourceKey: $resourceKey,
                resourceId: $entity->id(),
            ));
            
            $slugRepository->saveSlug(new SlugEntity(
                slug: $entity->get($field->name(), ''),
                locale: 'en',
                resourceKey: $resourceKey,
                resourceId: $entity->id(),
            ));
        }
        
        $slugs = $entity->get($field->name(), []);
        $oldSlugs = $oldEntity->get($field->name(), []);
        
        foreach($slugs as $locale => $slug) {
            if (isset($oldSlugs[$locale]) && $oldSlugs[$locale] === $slug) {
                continue;
            }
            
            if (!array_key_exists($locale, $field->locales())) {
                continue;
            }
            
            if (!is_string($slug)) {
                continue;
            }
            
            if (isset($oldSlugs[$locale]) && is_string($oldSlugs[$locale])) {
                $slugRepository->deleteSlug(new SlugEntity(
                    slug: $oldSlugs[$locale],
                    locale: $locale,
                    resourceKey: $resourceKey,
                    resourceId: $entity->id(),
                ));
            }
            
            $slugRepository->saveSlug(new SlugEntity(
                slug: $slug,
                locale: $locale,
                resourceKey: $resourceKey,
                resourceId: $entity->id(),
            ));
        }
    }
    
    /**
     * Processes the deleted action.
     *
     * @param ActionInterface $action
     * @param Slug $field
     * @return void
     */
    public function processDeleted(
        ActionInterface $action,
        Slug $field,
        SlugRepositoryInterface $slugRepository,
    ): void {
        $entity = $field->entity();
        $resourceKey = $action->controller()->resourceName();
        
        if (! $field->isTranslatable()) {
            $slugRepository->deleteSlug(new SlugEntity(
                slug: $entity->get($field->name(), ''),
                locale: 'en',
                resourceKey: $resourceKey,
                resourceId: $entity->id(),
            ));
        }
        
        $slugs = $entity->get($field->name(), []);

        foreach($slugs as $locale => $slug) {
            if (!array_key_exists($locale, $field->locales())) {
                continue;
            }
            
            if (!is_string($slug)) {
                continue;
            }
            
            $slugRepository->deleteSlug(new SlugEntity(
                slug: $slug,
                locale: $locale,
                resourceKey: $resourceKey,
                resourceId: $entity->id(),
            ));
        }
    }
    
    /**
     * Processes the index action.
     *
     * @param FieldInterface $field
     * @return void
     */
    public function processIndexAction(FieldInterface $field): void
    {
        if (! $this->hasValueFormatter(action: 'index')) {
            $this->formatValue(formatter: new Field\Formatter\Str(trimWidth: 100), action: 'index');
        }
        
        $value = $field->entity()->get(name: $field->name(), locale: $field->locale());
        
        $value = $this->formattingValue(action: 'index', value: $value, field: $field);
        
        $field->html(Str::esc($value));
    }
    
    /**
     * Processes the show action.
     *
     * @param FieldInterface $field
     * @param ViewInterface $view
     * @return void
     */
    public function processShow(FieldInterface $field, ViewInterface $view): void
    {
        $value = $field->entity()->get($field->name(), '', $field->locale());
        
        $value = $this->formattingValue(action: 'show', value: $value, field: $field);

        $field->html($view->render(
            view: 'crud/field/show/field',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'formatter' => fn (mixed $value) => $this->formattingValue(action: 'show', value: $value, field: $field),
                'renderLabel' => true,
                'text' => $value,
            ],
        ));
    }
}