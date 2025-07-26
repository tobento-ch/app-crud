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

namespace Tobento\App\Crud\Test\Url;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Button\Button;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Test\Factory;
use Tobento\App\Crud\Url\UrlResolver;
use Tobento\App\Crud\Url\UrlResolverInterface;
use Tobento\Service\Repository\Storage\Column;
use Closure;

class UrlResolverTest extends TestCase
{
    protected function getController(null|array $createItems = null): AbstractCrudController
    {
        $repository = Factory::createStorageRepository(
            table: 'users',
            columns: [
                Column\Id::new(),
                Column\Text::new('sku'),
            ],
        );

        if (is_array($createItems)) {
            $insertedItems = $repository->storage()->table('users')
                ->chunk(length: 20000)
                ->insertItems(items: $createItems);
            // as generator:
            foreach($insertedItems as $user) {}
        }
        
        return Factory::createCrudController(
            repository: $repository,
            resourceName: 'users',
            fields: [
                //Field\Text::new('id'),
                //Field\Text::new('email'),
            ],
            actions: [
                //Action\Index::new('Users'),
            ],
        );
    }
    
    public function testThatImplementsUrlResolverInterface()
    {
        $ur = new UrlResolver(router: Factory::createRouter());
        $this->assertInstanceof(UrlResolverInterface::class, $ur);
    }
    
    public function testResolveButtonUrlMethodWithLinkToUrl()
    {
        $ur = new UrlResolver(router: Factory::createRouter());
        
        $url = $ur->resolveButtonUrl(
            button: Button::new(label: 'label', group: 'group')->linkToUrl('url'),
            action: Action\Create::new(),
            entity: null,
        );
        
        $this->assertSame('url', $url);
    }
    
    public function testResolveButtonUrlMethodWithLinkToUrlClosure()
    {
        $ur = new UrlResolver(router: Factory::createRouter());
        
        $url = $ur->resolveButtonUrl(
            button: Button::new(label: 'label', group: 'group')->linkToUrl(function (EntityInterface $entity) {
                return 'https://example.com/invoice/'.$entity->id();
            }),
            action: Action\Edit::new(),
            entity: new Entity(['id' => 1]),
        );
        
        $this->assertSame('https://example.com/invoice/1', $url);
    }
    
    public function testResolveButtonUrlMethodWithLinkToUrlClosureWithoutEntity()
    {
        $ur = new UrlResolver(router: Factory::createRouter());
        
        $url = $ur->resolveButtonUrl(
            button: Button::new(label: 'label', group: 'group')->linkToUrl(function (EntityInterface $entity) {
                return 'https://example.com/invoice/'.$entity->id();
            }),
            action: Action\Create::new(),
            entity: null,
        );
        
        $this->assertSame('https://example.com/invoice/0', $url);
    }

    public function testResolveButtonUrlMethodWithLinkToAction()
    {
        $router = Factory::createRouter();
        $ur = new UrlResolver(router: $router);
        $controller = $this->getController();
        $router->resource($controller->resourceName(), $controller);
        $action = Action\Create::new()
            ->setController($controller)
            ->setActions(new Action\Actions(
                Action\Edit::new()->setController($controller),
            ));
        
        $url = $ur->resolveButtonUrl(
            button: Button::new(label: 'label', group: 'group')->linkToAction('edit'),
            action: $action,
            entity: new Entity(['id' => 1]),
        );
        
        $this->assertSame('https://example.com/users/1/edit', $url);
    }
    
    public function testResolveButtonUrlMethodWithLinkToActionReturnsEmptyStringIfActionDoesNotExist()
    {
        $ur = new UrlResolver(router: Factory::createRouter());
        
        $url = $ur->resolveButtonUrl(
            button: Button::new(label: 'label', group: 'group')->linkToAction('edit'),
            action: Action\Edit::new(),
            entity: new Entity(['id' => 1]),
        );
        
        $this->assertSame('', $url);
    }
    
    public function testResolveButtonUrlMethodWithLinkToRoute()
    {
        $router = Factory::createRouter();
        $router->get('invoice/{id}', function () {})->name('viewInvoice');
        $ur = new UrlResolver(router: $router);
        
        $url = $ur->resolveButtonUrl(
            button: Button::new(label: 'label', group: 'group')->linkToRoute('viewInvoice', ['id' => 5]),
            action: Action\Create::new()->setController($this->getController()),
            entity: new Entity(['id' => 1]),
        );
        
        $this->assertSame('https://example.com/invoice/5', $url);
    }
    
    public function testResolveButtonUrlMethodWithLinkToRouteClosure()
    {
        $router = Factory::createRouter();
        $router->get('invoice/{id}', function () {})->name('viewInvoice');
        $ur = new UrlResolver(router: $router);
        
        $url = $ur->resolveButtonUrl(
            button: Button::new(label: 'label', group: 'group')->linkToRoute('viewInvoice', function (EntityInterface $entity) {
                return ['id' => $entity->id()];
            }),
            action: Action\Edit::new()->setController($this->getController()),
            entity: new Entity(['id' => 3]),
        );
        
        $this->assertSame('https://example.com/invoice/3', $url);
    }
    
    public function testResolveButtonUrlMethodWithLinkToRouteClosureReturningNull()
    {
        $router = Factory::createRouter();
        $router->get('invoice/{id}', function () {})->name('viewInvoice');
        $ur = new UrlResolver(router: $router);
        
        $url = $ur->resolveButtonUrl(
            button: Button::new(label: 'label', group: 'group')->linkToRoute('viewInvoice', function (EntityInterface $entity) {
                return null;
            }),
            action: Action\Edit::new()->setController($this->getController()),
            entity: new Entity(['id' => 3]),
        );
        
        $this->assertSame('', $url);
    }
    
    public function testResolveButtonUrlMethodWithLinkToRouteSetsEmptyUrlStringIfRouteNotFound()
    {
        $router = Factory::createRouter();

        $ur = new UrlResolver(router: $router);
        
        $url = $ur->resolveButtonUrl(
            button: Button::new(label: 'label', group: 'group')->linkToRoute('viewInvoice', ['id' => 5]),
            action: Action\Create::new()->setController($this->getController()),
            entity: new Entity(['id' => 1]),
        );
        
        $this->assertSame('', $url);
    }
    
    public function testResolveActionLinkToUrlMethodWithLinkToUrl()
    {
        $ur = new UrlResolver(router: Factory::createRouter());
        
        $url = $ur->resolveActionLinkToUrl(
            action: Action\Create::new()->linkToUrl('url'),
            entity: null,
        );
        
        $this->assertSame('url', $url);
    }
    
    public function testResolveActionLinkToUrlMethodWithLinkToUrlClosure()
    {
        $ur = new UrlResolver(router: Factory::createRouter());
        
        $url = $ur->resolveActionLinkToUrl(
            action: Action\Edit::new()->linkToUrl(function (EntityInterface $entity) {
                return 'https://example.com/invoice/'.$entity->id();
            }),
            entity: new Entity(['id' => 1]),
        );
        
        $this->assertSame('https://example.com/invoice/1', $url);
    }
    
    public function testResolveActionLinkToUrlMethodWithLinkToUrlClosureWithoutEntity()
    {
        $ur = new UrlResolver(router: Factory::createRouter());
        
        $url = $ur->resolveActionLinkToUrl(
            action: Action\Edit::new()->linkToUrl(function (EntityInterface $entity) {
                return 'https://example.com/invoice/'.$entity->id();
            }),
            entity: null,
        );
        
        $this->assertSame('https://example.com/invoice/0', $url);
    }

    public function testResolveActionLinkToUrlMethodWithLinkToAction()
    {
        $router = Factory::createRouter();
        $ur = new UrlResolver(router: $router);
        $controller = $this->getController();
        $router->resource($controller->resourceName(), $controller);
        $action = Action\Create::new()
            ->setController($controller)
            ->setActions(new Action\Actions(
                Action\Edit::new()->setController($controller),
            ));
        
        $url = $ur->resolveActionLinkToUrl(
            action: $action->linkToAction('edit'),
            entity: new Entity(['id' => 1]),
        );
        
        $this->assertSame('https://example.com/users/1/edit', $url);
    }
    
    public function testResolveActionLinkToUrlMethodWithLinkToActionReturnsEmptyStringIfActionDoesNotExist()
    {
        $ur = new UrlResolver(router: Factory::createRouter());
        
        $url = $ur->resolveActionLinkToUrl(
            action: Action\Edit::new()->linkToAction('edit'),
            entity: new Entity(['id' => 1]),
        );
        
        $this->assertSame('', $url);
    }
    
    public function testResolveActionLinkToUrlMethodWithLinkToRoute()
    {
        $router = Factory::createRouter();
        $router->get('invoice/{id}', function () {})->name('viewInvoice');
        $ur = new UrlResolver(router: $router);
        
        $url = $ur->resolveActionLinkToUrl(
            action: CustomAction::new()
                ->linkToRoute('viewInvoice', ['id' => 5])
                ->setController($this->getController()),
            entity: new Entity(['id' => 1]),
        );
        
        $this->assertSame('https://example.com/invoice/5', $url);
    }
    
    public function testResolveActionLinkToUrlMethodWithLinkToRouteClosure()
    {
        $router = Factory::createRouter();
        $router->get('invoice/{id}', function () {})->name('viewInvoice');
        $ur = new UrlResolver(router: $router);
        
        $url = $ur->resolveActionLinkToUrl(
            action: CustomAction::new()->linkToRoute('viewInvoice', function (EntityInterface $entity) {
                return ['id' => $entity->id()];
            })->setController($this->getController()),
            entity: new Entity(['id' => 3]),
        );
        
        $this->assertSame('https://example.com/invoice/3', $url);
    }
    
    public function testResolveActionUrlMethodWithUrlString()
    {
        $ur = new UrlResolver(router: Factory::createRouter());
        
        $url = $ur->resolveActionUrl(
            action: Action\Create::new()->url('url'),
            entity: null,
        );
        
        $this->assertSame('url', $url);
    }
    
    public function testResolveActionUrlMethodWithUrlClosure()
    {
        $ur = new UrlResolver(router: Factory::createRouter());
        
        $url = $ur->resolveActionUrl(
            action: Action\Edit::new()->url(function (EntityInterface $entity) {
                return 'https://example.com/edit/'.$entity->id();
            }),
            entity: new Entity(['id' => 3]),
        );
        
        $this->assertSame('https://example.com/edit/3', $url);
    }
    
    public function testResolveActionUrlMethodWithUrlRouteClosure()
    {
        $router = Factory::createRouter();
        $router->get('invoice/{id}', function () {})->name('viewInvoice');
        $ur = new UrlResolver(router: $router);
        
        $url = $ur->resolveActionUrl(
            action: CustomAction::new()->setRoute('viewInvoice', function (EntityInterface $entity) {
                return ['id' => $entity->id()];
            })->setController($this->getController()),
            entity: new Entity(['id' => 3]),
        );
        
        $this->assertSame('https://example.com/invoice/3', $url);
    }
    
    public function testResolveActionUrlMethodWithUrlRouteArray()
    {
        $router = Factory::createRouter();
        $router->get('invoice/{id}', function () {})->name('viewInvoice');
        $ur = new UrlResolver(router: $router);
        
        $url = $ur->resolveActionUrl(
            action: CustomAction::new()->setRoute('viewInvoice', ['id' => 5])->setController($this->getController()),
            entity: new Entity(['id' => 3]),
        );
        
        $this->assertSame('https://example.com/invoice/5', $url);
    }
}

final class CustomAction extends Action\AbstractAction
{
    public static function new(): static
    {
        return new static();
    }
    
    public function name(): string
    {
        return 'custom';
    }
    
    public function setRoute(string $name, array|Closure $parameters = []): static
    {
        $this->route($name, $parameters);
        return $this;
    }    
}