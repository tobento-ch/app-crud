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

namespace Tobento\App\Crud\Url;

use Tobento\Service\Routing\RouterInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Button\ButtonInterface;
use Tobento\App\Crud\Button\Buttons;
use Tobento\App\Crud\Button\ButtonsAwareInterface;
use Tobento\App\Crud\Button\ButtonsInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Closure;

/**
 * UrlResolver
 */
class UrlResolver implements UrlResolverInterface
{
    /**
     * Create a new UrlResolver.
     *
     * @param RouterInterface $router
     */
    public function __construct(
        protected RouterInterface $router,
    ) {}

    /**
     * Returns the resolved buttons.
     *
     * @param ButtonsInterface $buttons
     * @param ActionInterface $action
     * @param null|EntityInterface $entity
     * @return ButtonsInterface
     */
    public function resolveButtonsUrl(
        ButtonsInterface $buttons,
        ActionInterface $action,
        null|EntityInterface $entity = null,
    ): ButtonsInterface {
        $entity = $entity ?: $action->entity();
        $resolvedButtons = [];

        foreach($buttons as $button) {
            if ($button instanceof ButtonInterface && $button instanceof ButtonsAwareInterface) {
                $btns = $this->resolveButtonsUrl($button->getButtons(), $action, $entity);
                $resolvedButtons[] = $button->withEntity($entity)->withButtons($btns);
                continue;
            }
            
            $url = $this->resolveButtonUrl($button, $action, $entity);

            $resolvedButtons[] = $button->withUrl($url)->withEntity($entity);
        }
        
        return new Buttons(...$resolvedButtons);
    }
    
    /**
     * Returns the resolved button url.
     *
     * @param ButtonInterface $button
     * @param ActionInterface $action
     * @param null|EntityInterface $entity
     * @return string
     */
    public function resolveButtonUrl(
        ButtonInterface $button,
        ActionInterface $action,
        null|EntityInterface $entity = null,
    ): string {
        $entity = $entity ?: $action->entity();
        
        if (!is_null($url = $button->getLinkToUrl())) {
            return $url instanceof Closure ? $url($entity) : $url;
        }
        
        if (!is_null($actionName = $button->getLinkToAction())) {
            $linkAction = $action->actions()->get(name: $actionName);

            if (is_null($linkAction)) {
                return '';
            }

            return $this->resolveActionUrl($linkAction, $entity);
        }
        
        if (is_null($button->getLinkToRoute())) {
            return '';
        }
        
        // handle route:
        [$routeName, $routeParameters] = $button->getLinkToRoute();
        
        if (is_callable($routeParameters)) {
            $routeParameters = $routeParameters($entity);
        }
        
        $routeName = $this->resolveRouteName($routeName, $action);
        
        return (string)$this->router->url($routeName, $routeParameters);
    }
    
    /**
     * Returns the resolved action link to url.
     *
     * @param ActionInterface $action
     * @param null|EntityInterface $entity
     * @return string
     */
    public function resolveActionLinkToUrl(
        ActionInterface $action,
        null|EntityInterface $entity = null,
    ): string {
        
        $entity = $entity ?: $action->entity();
        
        if (!is_null($url = $action->getLinkToUrl())) {
            return $url instanceof Closure ? $url($entity) : $url;
        }
        
        if (!is_null($actionName = $action->getLinkToAction())) {
            $linkAction = $action->actions()->get(name: $actionName);

            if (is_null($linkAction)) {
                return '';
            }

            return $this->resolveActionUrl($linkAction, $entity);
        }
        
        if (is_null($action->getLinkToRoute())) {
            return '';
        }
        
        [$routeName, $routeParameters] = $action->getLinkToRoute();
        
        if (is_callable($routeParameters)) {
            $routeParameters = $routeParameters($entity);
        }
        
        $routeName = $this->resolveRouteName($routeName, $action);
        
        return (string)$this->router->url($routeName, $routeParameters);
    }
    
    /**
     * Returns the resolved action url.
     *
     * @param ActionInterface $action
     * @param null|EntityInterface $entity
     * @return string
     */
    public function resolveActionUrl(
        ActionInterface $action,
        null|EntityInterface $entity = null,
    ): string {
                
        $entity = $entity ?: $action->entity();
        
        if (!is_null($url = $action->getRawUrl())) {
            return $url instanceof Closure ? $url($entity) : $url;
        }
        
        if (is_null($action->getRoute())) {
            return '';
        }
        
        [$routeName, $routeParameters] = $action->getRoute();
        
        if (is_callable($routeParameters)) {
            $routeParameters = $routeParameters($entity);
        }
        
        $routeName = $this->resolveRouteName($routeName, $action);
        
        return (string)$this->router->url($routeName, $routeParameters);
    }
    
    /**
     * Returns the resolved route name.
     *
     * @param string $routeName
     * @param ActionInterface $action
     * @return string
     */
    protected function resolveRouteName(string $routeName, ActionInterface $action): string
    {
        $name = $action->controller()->resourceName();
        
        return str_replace('{name}', $name, $routeName);
    }
}