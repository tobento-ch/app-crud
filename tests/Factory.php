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

namespace Tobento\App\Crud\Test;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\ActionProcessor;
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Url;
use Tobento\Service\Container\Container;
use Tobento\Service\Icon\Icon;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\StorageRepository;
use Tobento\Service\Repository\Storage\StorageEntityFactoryInterface;
use Tobento\Service\Repository\Storage\Column\ColumnsInterface;
use Tobento\Service\Requester\Requester;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Routing;
use Tobento\Service\Storage\StorageInterface;
use Tobento\Service\Storage\InMemoryStorage;
use Tobento\Service\Support\Str;
use Tobento\Service\View\ViewInterface;
use Tobento\Service\View\View;
use Tobento\Service\View\PhpRenderer;
use Tobento\Service\View\Data;
use Tobento\Service\View\Assets;
use Tobento\Service\Dir\Dirs;
use Tobento\Service\Dir\Dir;
use Tobento\Service\Form\Form;
use Tobento\Service\Tag\Tag;
use Tobento\Service\Translation;
use Tobento\Service\Validation;
use Closure;

class Factory
{
    /**
     * Create a new storage repository.
     */
    public static function createStorageRepository(
        string $table,
        iterable|ColumnsInterface $columns,
        null|StorageInterface $storage = null,
        null|StorageEntityFactoryInterface $entityFactory = null,
    ): RepositoryInterface {
        
        if (is_null($storage)) {
            $storage = new  InMemoryStorage(items: []);
        }
        
        return new class(
            $storage,
            $table,
            $columns,
            $entityFactory,
        ) extends StorageRepository {
            //
        };
    }

    /**
     * Create a new crud controller.
     */
    public static function createCrudController(
        RepositoryInterface $repository,
        string $resourceName = 'items',
        array $fields = [],
        array $actions = [],
        array $filters = [],
        string $entityIdName = 'id'
    ): AbstractCrudController {
        return new class(
            $repository,
            $resourceName,
            $fields,
            $actions,
            $filters,
            $entityIdName
        ) extends AbstractCrudController {
            public function __construct(
                RepositoryInterface $repository,
                protected string $resourceName = 'items',
                protected array $fields = [],
                protected array $actions = [],
                protected array $filters = [],
                protected string $entityIdName = 'id',
            ) {
                $this->repository = $repository;
            }
            
            public function resourceName(): string
            {
                return $this->resourceName;
            }
            
            protected function entityIdName(): string
            {
                return $this->entityIdName;
            }

            protected function configureFields(ActionInterface $action): iterable|FieldsInterface
            {
                return $this->fields;
            }

            protected function configureActions(): iterable|ActionsInterface
            {
                return $this->actions;
            }
            
            protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
            {
                return $this->filters;
            }
        };
    }
    
    /**
     * View for simple field action view process tests. For more complex views use Feature tests.
     */
    public static function createView(): ViewInterface
    {
        $view = new View(
            new PhpRenderer(
                new Dirs(
                    new Dir(realpath(__DIR__.'/../resources/views/')),
                )
            ),
            new Data(),
            new Assets('public/assets/', 'https://www.example.com/assets/')
        );
                
        $view->addMacro('form', function() {
            return new Form();
        });
        
        $translator = static::createTranslator();
        
        $view->addMacro('trans', function(string $message, array $parameters = []) use ($translator) {
            return $translator->trans($message, $parameters);
        });
        
        $view->addMacro('etrans', function(string $message, array $parameters = []) use ($translator) {
            return Str::esc($translator->trans($message, $parameters));
        });
        
        $view->addMacro('icon', function(string $icon) {
            return new Icon(name: $icon, tag: new Tag('i', $icon));
        });
        
        return $view;
    }
    
    /**
     * Returns the created translator.
     */
    public static function createTranslator(): Translation\TranslatorInterface
    {
        return new Translation\Translator(
            resources: new Translation\Resources(
                new Translation\Resource('*', 'de', [
                    'Hello World' => 'Hallo Welt',
                ]),
            ),
            modifiers: new Translation\Modifiers(
                new Translation\Modifier\ParameterReplacer(),
            ),
            missingTranslationHandler: new Translation\MissingTranslationHandler(),
            locale: 'en',
        );
    }
    
    /**
     * Returns the created router.
     */
    public static function createRouter(
        string $requestMethod = 'GET',
        string $requestUri = '',
        null|ContainerInterface $container = null
    ): Routing\RouterInterface {
        $container = $container ?: new Container();

        return new Routing\Router(
            new Routing\RequestData(
                $requestMethod,
                $requestUri,
                'example.com',
            ),
            new Routing\UrlGenerator(
                'https://example.com',
                'a-random-32-character-secret-signature-key',
            ),
            new Routing\RouteFactory(),
            new Routing\RouteDispatcher($container, new Routing\Constrainer\Constrainer()),
            new Routing\RouteHandler($container),
            new Routing\MatchedRouteHandler($container),
            new Routing\RouteResponseParser(),
        );
    }
    
    /**
     * Returns the created validator.
     */
    public static function createValidator(
        null|ContainerInterface $container = null
    ): Validation\ValidatorInterface {
        $container = $container ?: new Container();
        
        return new Validation\Validator(
            rules: new Validation\DefaultRules(
                ruleFactory: new Validation\AutowiringRuleFactory($container),
            ),
        );
    }
    
    /**
     * Returns the created action processor.
     */
    public static function createActionProcessor(
        null|Routing\RouterInterface $router = null,
        null|ContainerInterface $container = null
    ): ActionProcessorInterface {
        $container = $container ?: new Container();
        $container->set(ViewInterface::class, static::createView());
        $router = $router ?: static::createRouter(container: $container);
        $container->set(Routing\RouterInterface::class, $router);
        $container->set(Validation\ValidatorInterface::class, static::createValidator(container: $container));
        $container->set(RequesterInterface::class, static::createRequester());
        
        $actionProcessor = new ActionProcessor(
            container: $container,
            urlResolver: new Url\UrlResolver(router: $router),
            translator: null,
            languages: null,
        );
        
        $container->set(ActionProcessorInterface::class, $actionProcessor);
        
        return $actionProcessor;
    }
    
    /**
     * Returns the created requester.
     */
    public static function createRequester(string $method = 'GET', string $uri = 'https://example.com'): RequesterInterface
    {
        return new Requester(
            new Psr17Factory()->createServerRequest(
                method: $method,
                uri: $uri,
            ),        
        );
    }
}