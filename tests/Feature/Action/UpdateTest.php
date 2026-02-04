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
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Test\Factory;
use Tobento\App\Testing\Http\AssertableJson;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Storage\StorageInterface;

class UpdateTest extends \Tobento\App\Crud\Test\Feature\TestCase
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
                new Column\Json('options'),
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
                new Field\Text('options.color'),
            ],
            actions: [
                new Action\Update()->unupdatable(
                    [3],
                    fn (EntityInterface $entity): string => sprintf('ID %s unupdatable because of...', $entity->id())
                ),
                new Action\Edit(),
                new Action\Index(),
            ],
        );
    }
    
    public function testRedirectsToPrevUriIfUpdateActionNotSpecified()
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
        $http->request(method: 'PUT', uri: $this->generateUpdateUri(id: 1));
        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodyContains('Action update not found.');
    }
    
    public function testRedirectsToPrevUriIfEntityNotFound()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'PUT', uri: $this->generateUpdateUri(id: 1));
        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodyContains('Record with the ID 1 not found.');
    }
    
    public function testUpdatesEntity()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'PUT', uri: $this->generateUpdateUri(id: 1))->body([
            'email' => 'new@example.com',
        ]);
        
        $this->getSeedFactory(['email' => 'tom@example.com'])->createOne();
        
        $http->followRedirects()->assertStatus(200)->assertCrudIndexEntityCount(1);

        $this->assertSame('new@example.com', $this->getCrudRepository()->findById(1)->get('email'));
    }
    
    public function testRedirectsToPrevUriIfValidationFails()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateEditUri(id: 1));
        $http->request(method: 'PUT', uri: $this->generateUpdateUri(id: 1))->body([
            'email' => [55],
        ]);
        
        $this->getSeedFactory(['email' => 'tom@example.com'])->createOne();
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'email', errorText: 'The email must be a string.');

        $this->assertSame('tom@example.com', $this->getCrudRepository()->findById(1)->get('email'));
    }
    
    public function testUpdatesEntityUsingAjax()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'PUT', uri: $this->generateUpdateUri(id: 1))->body([
            'options' => ['color' => 'blue'],
        ])->headers(['X-Requested-With' => 'XMLHttpRequest']);
        
        $this->getSeedFactory(['options' => ['color' => 'red']])->createOne();
        
        $http->followRedirects()->assertStatus(200)->assertCrudIndexEntityCount(1);

        $this->assertSame(['color' => 'blue'], $this->getCrudRepository()->findById(1)->get('options'));
    }
    
    public function testUnupdatableEntitiesCannotBeUpdated()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateEditUri(id: 3));
        $http->request(method: 'PUT', uri: $this->generateUpdateUri(id: 3))->body([
            'email' => 'new@example.com',
        ]);
        
        $this->getSeedFactory(['email' => 'tom@example.com'])->times(3)->create();

        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodyContains('ID 3 unupdatable because of...');

        $this->assertSame('tom@example.com', $this->getCrudRepository()->findById(3)->get('email'));
    }
    
    public function testNextActionRedirectsIfNotSupportingMethod()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'PUT', uri: $this->generateUpdateUri(id: 1))->body([
            'email' => 'new@example.com',
            'next_action' => 'edit',
        ]);
        
        $this->getSeedFactory(['email' => 'tom@example.com'])->createOne();

        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodyContains('Edit'); // lands on edit page

        $this->assertSame('new@example.com', $this->getCrudRepository()->findById(1)->get('email'));
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
                    new Action\Update(),
                    new TestNextUpdateAction(),
                ],
            );
        });

        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(
            method: 'PUT',
            uri: $this->generateUpdateUri(id: 1),
            body: [
                'email' => 'new@example.com',
                'next_action' => 'next',
            ]
        );
        
        $this->getSeedFactory(['email' => 'tom@example.com'])->createOne();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('executed');

        $this->assertSame('new@example.com', $this->getCrudRepository()->findById(1)->get('email'));
    }
    
    public function testLive()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'PUT',
            uri: $this->generateUpdateUri(id: 1),
            headers: ['X-Requested-With' => 'XMLHttpRequest', 'Content-Type' => 'application/json', 'X-Crud-Live' => '1'],
            body: ['email' => 'tom@example.com'],
        );
        
        $this->getSeedFactory(['email' => 'tom@example.com'])->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertContentType('application/json')
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
                     ->has(key: 'html')
            );

        $this->assertSame(1, $this->getCrudRepository()->count());
    }
}

class TestNextUpdateAction extends Action\AbstractAction
{
    protected array $supportedRequestMethods = ['PUT'];

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