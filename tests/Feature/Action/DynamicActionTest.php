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

use Psr\Http\Message\ResponseInterface;
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

class DynamicActionTest extends \Tobento\App\Crud\Test\Feature\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../../..');
        
$app->boot(\Tobento\App\Boot\ErrorHandling::class);
$app->booting();
$app->get(\Tobento\Service\Config\ConfigInterface::class)->set('app.debug', true);
        
        
        $app->boot(Crud::class);
        
        /*$app->on(Crud::class, function(Crud $crud): void {
            $route = $crud->routeDynamicAction(
                controller: App\ProductsController::class,
                localized: true,
            );
        });*/
        
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
            ],
            actions: [
                new Action\Index(),
                new DynamicAction(),
            ],
        );
    }

    public function testGetRequest()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'users/action/dynamic');

        $http->response()->assertStatus(200)->assertJson(['id' => null]);
    }
    
    public function testPostRequest()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'users/action/dynamic')->body([
            'key' => 'value',
        ]);

        $http->response()->assertStatus(200)->assertJson(['id' => null]);
    }
    
    public function testPutRequest()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'PUT', uri: 'users/action/dynamic')->body([
            'key' => 'value',
        ]);

        $http->response()->assertStatus(200)->assertJson(['id' => null]);
    }
    
    public function testPatchRequest()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: 'users/action/dynamic')->body([
            'key' => 'value',
        ]);

        $http->response()->assertStatus(200)->assertJson(['id' => null]);
    }
    
    public function testDeleteRequest()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'DELETE', uri: 'users/action/dynamic')->body([
            'key' => 'value',
        ]);

        $http->response()->assertStatus(200)->assertJson(['id' => null]);
    }
    
    public function testRequestWithId()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'users/action/dynamic/24');

        $http->response()->assertStatus(200)->assertJson(['id' => '24']);
    }
}

final class DynamicAction extends Action\AbstractAction
{
    public function __construct()
    {
        $this->route('{name}.dynamic', function(EntityInterface $entity): array {
            return ['id' => $entity->id(), 'action' => 'dynamic'];
        });
        $this->linkToAction('dynamic');
    }
    
    public function name(): string
    {
        return 'dynamic';
    }
    
    public function getHandler(): callable
    {
        return [$this, 'handle'];
    }
    
    public function handle(null|int|string $id, ResponserInterface $responser): ResponseInterface
    {
        return $responser->json(
            data: ['id' => $id],
            code: 200,
        );
    }
}