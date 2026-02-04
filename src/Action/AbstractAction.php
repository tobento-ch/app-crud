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

use Closure;
use LogicException;
use Psr\Container\ContainerInterface;
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Url\HasLinksTo;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Button\ButtonInterface;
use Tobento\App\Crud\Button\ButtonsInterface;
use Tobento\App\Crud\Button\Buttons;
use Tobento\App\Crud\Button\ConfigurableButtons;
use Tobento\App\Crud\Entity\EntitiesInterface;
use Tobento\App\Crud\Entity\Entities;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\App\Crud\Input\Input;
use Tobento\Service\Translation\TranslatorInterface;

abstract class AbstractAction implements ActionInterface
{
    use HasLinksTo;
    use ConfigurableButtons;
    
    /**
     * @var array<int, string>
     */
    protected array $supportedRequestMethods = [];
    
    /**
     * @var null|FieldsInterface
     */
    protected null|FieldsInterface $fields = null;
    
    /**
     * @var null|string
     */
    protected null|string $fieldActionType = null;
    
    /**
     * @var null|FiltersInterface
     */
    protected null|FiltersInterface $filters = null;
    
    /**
     * @var null|ButtonsInterface
     */
    protected null|ButtonsInterface $buttons = null;
    
    /**
     * @var null|EntityInterface
     */
    protected null|EntityInterface $entity = null;
    
    /**
     * @var null|EntitiesInterface
     */
    protected null|EntitiesInterface $entities = null;
    
    /**
     * @var null|AbstractCrudController
     */
    protected null|AbstractCrudController $controller = null;
    
    /**
     * @var null|ContainerInterface
     */
    protected null|ContainerInterface $container = null;
    
    /**
     * @var null|ActionsInterface
     */
    protected null|ActionsInterface $actions = null;
    
    /**
     * @var null|InputInterface
     */
    protected null|InputInterface $input = null;
    
    /**
     * @var null|string|Closure
     */
    protected null|string|Closure $title = null;
    
    /**
     * @var null|string|Closure
     */
    protected null|string|Closure $url = null;
    
    /**
     * @var null|array
     */
    protected null|array $route = null;

    /**
     * @var string
     */
    protected string $resolvedUrl = '';
    
    /**
     * @var string
     */
    protected string $linkUrl = '';
    
    /**
     * @var string
     */
    protected string $view = '';
    
    /**
     * @var string
     */
    protected string $locale = 'en';
    
    /**
     * @var array<string, string>
     */
    protected array $locales = ['en' => 'EN'];
    
    /**
     * @var null|TranslatorInterface
     */
    protected null|TranslatorInterface $translator = null;
    
    /**
     * Returns the name.
     *
     * @return string
     */
    abstract public function name(): string;
    
    /**
     * Create a new Action.
     *
     * @param null|string|Closure $title
     */
    public function __construct(
        null|string|Closure $title = null,
    ) {
        $this->title = $title;
    }
    
    /**
     * Returns the handler processing the action.
     *
     * @return callable(mixed...): \Psr\Http\Message\ResponseInterface
     */
    abstract public function getHandler(): callable;
    
    /**
     * Checks whether this action supports the given HTTP request method.
     *
     * Actions may declare supported HTTP verbs by setting the
     * $supportedRequestMethods property (e.g., ['POST', 'PUT']).
     * If the array is empty, the action does not support any methods
     * for chained execution unless a subclass overrides this method.
     *
     * @param string $method The HTTP method to evaluate.
     * @return bool True if the action supports the method, otherwise false.
     */
    public function supportsRequestMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->supportedRequestMethods, true);
    }
    
    /**
     * Returns the title.
     *
     * @return string
     */
    public function title(): string
    {
        if (is_null($this->title)) {
            return ucfirst($this->name());
        }
        
        if (is_string($this->title)) {
            return $this->title;
        }
        
        $closure = $this->title;
        return $closure($this->entity());
    }
    
    /**
     * Sets the title.
     *
     * @param string|Closure $title
     * @return static $this;
     */
    public function setTitle(string|Closure $title): static
    {
        $this->title = $title;
        return $this;
    }
    
    /**
     * Sets the url for the action.
     *
     * @param string|Closure $url
     * @return static $this
     */
    public function url(string|Closure $url): static
    {
        $this->url = $url;
        return $this;
    }
    
    /**
     * Sets the url.
     *
     * @param string $url
     * @return static $this;
     */
    public function setUrl(string $url): static
    {
        $this->resolvedUrl = $url;
        return $this;
    }

    /**
     * Returns the raw url for the action.
     *
     * @return null|string|Closure
     */
    public function getRawUrl(): null|string|Closure
    {
        return $this->url;
    }
    
    /**
     * Returns the url for the action.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->resolvedUrl;
    }
    
    /**
     * Sets the route for the action.
     *
     * @param string $name
     * @param array|Closure $parameters
     * @return static $this
     */
    protected function route(string $name, array|Closure $parameters = []): static
    {
        $this->route = [$name, $parameters];
        return $this;
    }
    
    /**
     * Returns the route.
     *
     * @return null|array
     */
    public function getRoute(): null|array
    {
        return $this->route;
    }

    /**
     * Sets the link url.
     *
     * @param string $url
     * @return static $this;
     */
    public function setLinkUrl(string $url): static
    {
        $this->linkUrl = $url;
        return $this;
    }
    
    /**
     * Returns the link url for the action.
     *
     * @return string
     */
    public function getLinkUrl(): string
    {
        return $this->linkUrl;
    }
    
    /**
     * Sets the view.
     *
     * @param string $name
     * @return static $this;
     */
    public function view(string $name): static
    {
        $this->view = $name;
        return $this;
    }
    
    /**
     * Returns the view for the action.
     *
     * @return string
     */
    public function getView(): string
    {
        return $this->view;
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
    
    /**
     * Sets the locales.
     *
     * @param array<string, string> $locales
     * @return static $this
     */
    public function locales(array $locales): static
    {
        $this->locales = $locales;
        $locales = array_flip($locales);
        $firstKey = array_key_first($locales);
        $this->locale = is_null($firstKey) ? 'en' : $locales[$firstKey];
        return $this;
    }
    
    /**
     * Returns the locales.
     *
     * @return array<string, string>
     */
    public function getLocales(): array
    {
        return $this->locales;
    }
    
    /**
     * Sets the buttons.
     *
     * @param ButtonInterface|ButtonsInterface $buttons
     * @return static $this
     */
    public function setButtons(ButtonInterface|ButtonsInterface ...$buttons): static
    {
        $btns = new Buttons();
        
        foreach($buttons as $button) {
            if ($button instanceof ButtonInterface) {
                $btns->add($button);
            } else {
                $btns->add(...$button->all());
            }
        }
        
        $this->buttons = $btns;
        return $this;
    }
    
    /**
     * Returns the buttons.
     *
     * @return ButtonsInterface
     */
    public function buttons(): ButtonsInterface
    {
        if ($this->buttons instanceof ButtonsInterface) {
            return $this->buttons;
        }
        
        $this->buttons = new Buttons();
        
        return $this->applyButtonsConfig($this->buttons);
    }
    
    /**
     * Sets the fields.
     *
     * @param FieldsInterface $fields
     * @return static $this
     */
    public function setFields(FieldsInterface $fields): static
    {
        $this->fields = $fields;
        return $this;
    }
    
    /**
     * Returns the fields.
     *
     * @return FieldsInterface
     */
    public function fields(): FieldsInterface
    {
        if (is_null($this->fields)) {
            return new Fields();
        }
        
        return $this->fields;
    }
    
    /**
     * Sets the field action type. E.g. 'update', 'create', etc.
     *
     * @param string $type
     * @return static
     */
    public function fieldActionType(string $type): static
    {
        $this->fieldActionType = $type;
        return $this;
    }
    
    /**
     * Returns the field action type. E.g. 'update', 'create', etc.
     *
     * @return string
     */
    public function getFieldActionType(): string
    {
        return !is_null($this->fieldActionType) ? $this->fieldActionType : $this->name();
    }
    
    /**
     * Returns the fields actions.
     *
     * @return array<string, callable>
     */
    public function getFieldsActions(): array
    {
        return [];
    }
    
    /**
     * Sets the filters.
     *
     * @param FiltersInterface $filters
     * @return static $this
     */
    public function setFilters(FiltersInterface $filters): static
    {
        $this->filters = $filters;
        return $this;
    }
    
    /**
     * Returns the filters.
     *
     * @return FiltersInterface
     */
    public function filters(): FiltersInterface
    {
        if (is_null($this->filters)) {
            return new Filters();
        }
        
        return $this->filters;
    }
    
    /**
     * Sets the entities.
     *
     * @param EntitiesInterface $entities
     * @return static $this
     */
    public function setEntities(EntitiesInterface $entities): static
    {
        $this->entities = $entities;
        return $this;
    }
    
    /**
     * Returns the entities.
     *
     * @return EntitiesInterface
     */
    public function entities(): EntitiesInterface
    {
        if (is_null($this->entities)) {
            return new Entities();
        }
        
        return $this->entities;
    }
    
    /**
     * Sets the entity.
     *
     * @param EntityInterface $entity
     * @return static $this
     */
    public function setEntity(EntityInterface $entity): static
    {
        $this->entity = $entity;
        return $this;
    }
    
    /**
     * Returns the entity.
     *
     * @return EntityInterface
     */
    public function entity(): EntityInterface
    {
        if (is_null($this->entity)) {
            return new Entity();
        }
        
        return $this->entity;
    }

    /**
     * Sets the controller.
     *
     * @param AbstractCrudController $controller
     * @return static $this
     */
    public function setController(AbstractCrudController $controller): static
    {
        $this->controller = $controller;
        return $this;
    }
    
    /**
     * Returns the controller.
     *
     * @return AbstractCrudController
     */
    public function controller(): AbstractCrudController
    {
        if (!is_null($this->controller)) {
            return $this->controller;
        }
        
        throw new LogicException('Controller is not set!');
    }
    
    /**
     * Sets the container.
     *
     * @param ContainerInterface $container
     * @return static $this
     */
    public function setContainer(ContainerInterface $container): static
    {
        $this->container = $container;
        return $this;
    }
    
    /**
     * Returns the container.
     *
     * @return ContainerInterface
     */
    public function container(): ContainerInterface
    {
        if (!is_null($this->container)) {
            return $this->container;
        }
        
        throw new \LogicException('Container is not set!');
    }
    
    /**
     * Sets the actions.
     *
     * @param ActionsInterface $actions
     * @return static $this
     */
    public function setActions(ActionsInterface $actions): static
    {
        $this->actions = $actions;
        return $this;
    }
    
    /**
     * Returns the actions.
     *
     * @return ActionsInterface
     */
    public function actions(): ActionsInterface
    {
        if (is_null($this->actions)) {
            return new Actions();
        }
        
        return $this->actions;
    }
    
    /**
     * Sets the input.
     *
     * @param InputInterface $input
     * @return static $this
     */
    public function setInput(InputInterface $input): static
    {
        $this->input = $input;
        return $this;
    }
    
    /**
     * Returns the input.
     *
     * @return InputInterface
     */
    public function getInput(): InputInterface
    {
        if (is_null($this->input)) {
            $this->input = new Input();
        }
        
        return $this->input;
    }
    
    /**
     * Returns the field value from input when set; if not, uses the entity’s value, or defaults.
     *
     * @param string $field
     * @param mixed $default
     * @return mixed
     */
    public function value(string $field, mixed $default = null): mixed
    {
        $input = $this->getInput();
        
        if ($input->has($field)) {
            return $input->get($field);
        }
        
        $fieldObj = $this->fields()->get($field);
        
        if ($fieldObj) {
            return $fieldObj->entity()->get(name: $field, default: $default);
        }
        
        return $default;
    }
    
    /**
     * Sets the translator.
     *
     * @param TranslatorInterface $translator
     * @return static $this
     */
    public function setTranslator(TranslatorInterface $translator): static
    {
        $this->translator = $translator;
        return $this;
    }
    
    /**
     * Returns the translated message.
     *
     * @param string $message The message to translate.
     * @param array $parameters Any parameters for the message.
     * @param null|string $locale The locale or null to use the default.
     * @return string The translated message.
     */
    public function trans(string $message, array $parameters = [], null|string $locale = null): string
    {
        if ($this->translator) {
            return $this->translator->trans($message, $parameters, $locale);
        }
        
        return $message;
    }
}