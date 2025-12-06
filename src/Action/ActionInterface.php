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

use Tobento\App\Crud\Url\Linkable;
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Button\ButtonInterface;
use Tobento\App\Crud\Button\ButtonsInterface;
use Tobento\App\Crud\Entity\EntitiesInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\Translation\TranslatorInterface;
use Closure;

/**
 * ActionInterface
 */
interface ActionInterface extends Linkable
{
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string;
    
    /**
     * Returns the title.
     *
     * @return string
     */
    public function title(): string;
    
    /**
     * Sets the url for the action.
     *
     * @param string|Closure $url
     * @return static $this
     */
    public function url(string|Closure $url): static;
    
    /**
     * Sets the url.
     *
     * @param string $url
     * @return static $this;
     */
    public function setUrl(string $url): static;

    /**
     * Returns the raw url for the action.
     *
     * @return null|string|Closure
     */
    public function getRawUrl(): null|string|Closure;
    
    /**
     * Returns the url for the action.
     *
     * @return string
     */
    public function getUrl(): string;
    
    /**
     * Returns the route.
     *
     * @return null|array
     */
    public function getRoute(): null|array;

    /**
     * Sets the link url.
     *
     * @param string $url
     * @return static $this;
     */
    public function setLinkUrl(string $url): static;

    /**
     * Returns the link url for the action.
     *
     * @return string
     */
    public function getLinkUrl(): string;
    
    /**
     * Sets the view.
     *
     * @param string $name
     * @return static $this;
     */
    public function view(string $name): static;
    
    /**
     * Returns the view for the action.
     *
     * @return string
     */
    public function getView(): string;
    
    /**
     * Returns the locale.
     *
     * @return string
     */
    public function getLocale(): string;
    
    /**
     * Sets the locales.
     *
     * @param array<string, string> $locales
     * @return static $this
     */
    public function locales(array $locales): static;
    
    /**
     * Returns the locales.
     *
     * @return array<string, string>
     */
    public function getLocales(): array;
    
    /**
     * Sets the buttons.
     *
     * @param ButtonInterface|ButtonsInterface $buttons
     * @return static $this
     */
    public function setButtons(ButtonInterface|ButtonsInterface ...$buttons): static;
    
    /**
     * Returns the buttons.
     *
     * @return ButtonsInterface
     */
    public function buttons(): ButtonsInterface;
    
    /**
     * Returns the buttons applied with the configurations.
     *
     * @param ButtonsInterface $buttons
     * @return ButtonsInterface
     */
    public function applyButtonsConfig(ButtonsInterface $buttons): ButtonsInterface;
    
    /**
     * Sets the fields.
     *
     * @param FieldsInterface $fields
     * @return static $this
     */
    public function setFields(FieldsInterface $fields): static;
    
    /**
     * Returns the fields.
     *
     * @return FieldsInterface
     */
    public function fields(): FieldsInterface;
    
    /**
     * Returns the fields actions.
     *
     * @return array<string, callable>
     */
    public function getFieldsActions(): array;
    
    /**
     * Sets the filters.
     *
     * @param FiltersInterface $filters
     * @return static $this
     */
    public function setFilters(FiltersInterface $filters): static;
    
    /**
     * Returns the filters.
     *
     * @return FiltersInterface
     */
    public function filters(): FiltersInterface;
    
    /**
     * Sets the entities.
     *
     * @param EntitiesInterface $entities
     * @return static $this
     */
    public function setEntities(EntitiesInterface $entities): static;
    
    /**
     * Returns the entities.
     *
     * @return EntitiesInterface
     */
    public function entities(): EntitiesInterface;
    
    /**
     * Sets the entity.
     *
     * @param EntityInterface $entity
     * @return static $this
     */
    public function setEntity(EntityInterface $entity): static;
    
    /**
     * Returns the entity.
     *
     * @return EntityInterface
     */
    public function entity(): EntityInterface;

    /**
     * Sets the controller.
     *
     * @param AbstractCrudController $controller
     * @return static $this
     */
    public function setController(AbstractCrudController $controller): static;
    
    /**
     * Returns the controller.
     *
     * @return AbstractCrudController
     */
    public function controller(): AbstractCrudController;
    
    /**
     * Sets the actions.
     *
     * @param ActionsInterface $actions
     * @return static $this
     */
    public function setActions(ActionsInterface $actions): static;
    
    /**
     * Returns the actions.
     *
     * @return ActionsInterface
     */
    public function actions(): ActionsInterface;
    
    /**
     * Sets the input.
     *
     * @param InputInterface $input
     * @return static $this
     */
    public function setInput(InputInterface $input): static;
    
    /**
     * Returns the input.
     *
     * @return InputInterface
     */
    public function getInput(): InputInterface;
    
    /**
     * Returns the field value from input when set; if not, uses the entity’s value, or defaults.
     *
     * @param string $field
     * @param mixed $default
     * @return mixed
     */
    public function value(string $field, mixed $default = null): mixed;
    
    /**
     * Sets the translator.
     *
     * @param TranslatorInterface $translator
     * @return static $this
     */
    public function setTranslator(TranslatorInterface $translator): static;
    
    /**
     * Returns the translated message.
     *
     * @param string $message The message to translate.
     * @param array $parameters Any parameters for the message.
     * @param null|string $locale The locale or null to use the default.
     * @return string The translated message.
     */
    public function trans(string $message, array $parameters = [], null|string $locale = null): string;
}