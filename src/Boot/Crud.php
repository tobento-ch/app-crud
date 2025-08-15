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

namespace Tobento\App\Crud\Boot;

use Tobento\App\Boot;
use Tobento\App\Migration\Boot\Migration;
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\ActionProcessor;
use Tobento\App\Crud\FilterProcessorInterface;
use Tobento\App\Crud\FilterProcessor;
use Tobento\App\Crud\Url;
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Media;
use Tobento\App\Media\Upload\UploadedFileFactory;
use Tobento\App\Media\Upload\UploadedFileFactoryInterface;
use Tobento\App\Language\RouteLocalizerInterface;
use Tobento\Service\Routing\RouterInterface;

/**
 * Crud boot.
 */
class Crud extends Boot
{
    public const INFO = [
        'boot' => [
            'Migrates views and assets for default layout',
            'Implements needed interfaces',
        ],
    ];
    
    public const BOOT = [
        Migration::class,
        \Tobento\App\Message\Boot\Message::class,
        
        // HTTP:
        \Tobento\App\Http\Boot\ErrorHandler::class,
        \Tobento\App\Http\Boot\Routing::class,
        \Tobento\App\Http\Boot\Session::class,
        \Tobento\App\Http\Boot\Cookies::class,
        \Tobento\App\Http\Boot\RequesterResponser::class,
        \Tobento\App\Crud\Boot\HttpErrorHandler::class,
        
        // I18n:
        \Tobento\App\Language\Boot\Language::class,
        \Tobento\App\Translation\Boot\Translation::class,
        
        // Misc:
        \Tobento\App\Validation\Boot\Validator::class,
        \Tobento\App\Media\Boot\Media::class,
        \Tobento\App\Event\Boot\Event::class,
        \Tobento\App\Slugging\Boot\Slugging::class,
        \Tobento\App\HtmlSanitizer\Boot\HtmlSanitizer::class,
        
        // VIEW:
        \Tobento\App\View\Boot\Messages::class,
        \Tobento\App\View\Boot\View::class,
        \Tobento\App\View\Boot\Form::class,
        \Tobento\App\View\Boot\Table::class,
    ];
    
    /**
     * Boot application services.
     *
     * @param Migration $migration
     * @return void
     */
    public function boot(Migration $migration): void
    {
        // Migration:
        $migration->install(\Tobento\App\Crud\Migration\Crud::class);
        
        // Interfaces:
        $this->app->set(ActionProcessorInterface::class, ActionProcessor::class);
        $this->app->set(FilterProcessorInterface::class, FilterProcessor::class);
        $this->app->set(Url\UrlResolverInterface::class, Url\UrlResolver::class);
        
        if (!$this->app->has(UploadedFileFactoryInterface::class)) {
            $this->app->set(UploadedFileFactoryInterface::class, UploadedFileFactory::class);
        }
        
        $features = $this->app->get(Media\FeaturesInterface::class);
        
        if (!$features->has('picture')) {
            $this->app->boot(Media\Feature\Picture::class);
        }
    }
    
    /**
     * Route the given crud controller.
     *
     * @param string|AbstractCrudController $controller
     * @param array<array-key, string> $only
     * @param array<array-key, string> $except
     * @param array $middleware Middleware for all routes
     * @param bool $localized If to localize routes.
     * @return void
     */
    public function routeController(
        string|AbstractCrudController $controller,
        array $only = [],
        array $except = [],
        array $middleware = [],
        bool $localized = false,
    ): void {
        $router = $this->app->get(RouterInterface::class);
        $routeLocalizer = $this->app->get(RouteLocalizerInterface::class);

        $name = is_string($controller) ? $controller::RESOURCE_NAME : $controller->resourceName();
        
        $resource = $router->resource($localized ? '{?locale}/'.$name : $name, $controller)
            ->name($name)
            ->where('[a-z0-9]+');
        
        $resource->action(
            action: 'copy', 
            method: 'GET', 
            uri: '/{id}/copy',
            parameters: ['constraints' => ['id' => '[a-z0-9]+']],
        );
        
        if (!empty($only)) {
            $resource->only($only);
        }
        
        if (!empty($except)) {
            $resource->except($except);
        }
        
        if (!empty($middleware)) {
            $resource->middleware([], ...$middleware);
        }
        
        if ($localized) {
            $routeLocalizer->localizeRoute($resource);
        }

        if (!in_array('bulk', $except) || in_array('bulk', $only)) {

            $uri = $localized ? '{?locale}/'.$name.'/bulk/{name}' : $name.'/bulk/{name}';
            
            $bulkRoute = $router->post($uri, [$controller, 'bulk'])
                ->middleware(...$middleware)
                ->name($name.'.bulk');
            
            if ($localized) {
                $routeLocalizer->localizeRoute($bulkRoute);
            }
        }
    }
}