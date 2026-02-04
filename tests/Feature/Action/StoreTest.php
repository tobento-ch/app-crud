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

namespace Tobento\App\Crud\Test\Feature\Action;

use Tobento\App\AppInterface;
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Boot\Crud;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Test\Factory;
use Tobento\App\Testing\Http\AssertableJson;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Storage\StorageInterface;

class StoreTest extends \Tobento\App\Crud\Test\Feature\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../../..');
        $app->boot(Crud::class);
        return $app;
    }

    protected function createRepository(AppInterface $app): RepositoryInterface
    {
        return Factory::createStorageRepository(
            table: 'users',
            columns: [
                new Column\Id(),
                new Column\Text('email'),
                new Column\Text('firstname'),
                new Column\Text('lastname'),
            ],
            storage: $app->get(StorageInterface::class)->new(),
        );
    }
    
    protected function createCrudController(AppInterface $app): AbstractCrudController
    {
        return Factory::createCrudController(
            repository: $this->createRepository($app),
            resourceName: $this->getCrudControllerResourceName(),
            fields: [
                new Field\PrimaryId('id'),
                new Field\Text('email')->validate('string|email'),
                new Field\Text('firstname')->validate('string'),
                new Field\Text('lastname')->validate('string'),
            ],
            actions: [
                new Action\Store(),
                new Action\Create(),
                new Action\Index(),
                new Action\Edit(),
            ],
        );
    }
    
    public function testRedirectsToPrevUriIfStoreActionNotSpecified()
    {
        $this->withCrudController(function (AppInterface $app) {
            return Factory::createCrudController(
                repository: $this->createRepository($app),
                resourceName: $this->getCrudControllerResourceName(),
                fields: [],
                actions: [new Action\Index()],
            );
        });
        
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri());
        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodyContains('Action store not found.');
    }
    
    public function testStoresEntity()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'email' => 'tom@example.com',
        ]);
        
        $http->followRedirects()->assertStatus(200)->assertCrudIndexEntityCount(1);

        $this->assertSame(1, $this->getCrudRepository()->count());
    }
    
    public function testRedirectsToPrevUriIfValidationFails()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'email' => [55],
        ]);
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'email', errorText: 'The email must be a string.');

        $this->assertSame(0, $this->getCrudRepository()->count());
    }
    
    public function testNextActionRedirectsIfNotSupportingMethod()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'email' => 'new@example.com',
            'next_action' => 'edit',
        ]);

        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodyContains('Edit'); // lands on edit page

        $this->assertSame(1, $this->getCrudRepository()->count());
    }
    
    public function testNextActionExecutesDirectlyIfSupportingMethod()
    {
        $this->withCrudController(function (AppInterface $app) {
            return Factory::createCrudController(
                repository: $this->createRepository($app),
                resourceName: $this->getCrudControllerResourceName(),
                fields: [
                    new Field\PrimaryId('id'),
                    new Field\Text('email'),
                ],
                actions: [
                    new Action\Store(),
                    new TestNextStoreAction(),
                ],
            );
        });

        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(
            method: 'POST',
            uri: $this->generateStoreUri(),
            body: [
                'email' => 'new@example.com',
                'next_action' => 'next',
            ]
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('executed');

        $this->assertSame(1, $this->getCrudRepository()->count());
    }
    
    public function testLive()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: $this->generateStoreUri(),
            headers: ['X-Requested-With' => 'XMLHttpRequest', 'Content-Type' => 'application/json', 'X-Crud-Live' => '1'],
            body: ['email' => 'tom@example.com'],
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertContentType('application/json')
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
                     ->has(key: 'html')
            );

        $this->assertSame(0, $this->getCrudRepository()->count());
    }
}

class TestNextStoreAction extends Action\AbstractAction
{
    protected array $supportedRequestMethods = ['POST'];

    public function name(): string
    {
        return 'next';
    }
    
    public function getHandler(): callable
    {
        return [$this, 'handle'];
    }
    
    public function handle(ResponserInterface $responser): \Psr\Http\Message\ResponseInterface
    {
        return $responser->html('executed');
    }
}