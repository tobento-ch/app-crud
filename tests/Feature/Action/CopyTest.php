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
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Boot\Crud;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Test\Factory;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Storage\StorageInterface;

class CopyTest extends \Tobento\App\Crud\Test\Feature\TestCase
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
                new Action\Copy('Copy User'),
                new Action\Store(),
            ],
        );
    }
    
    public function testReturnsNotFoundIfCopyActionNotSpecified()
    {
        $this->withCrudController(function (AppInterface $app) {
            return Factory::createCrudController(
                repository: $this->createRepository($app),
                resourceName: $this->getCrudControllerResourceName(),
                fields: [],
                actions: [],
            );
        });
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCopyUri(id: 1));
        $http->response()->assertStatus(404);
    }

    public function testReturnsNotFoundIfEntityIsNotFound()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCopyUri(id: 1));
        $http->response()->assertStatus(404);
    }
    
    public function testCopyScreenIsRendered()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCopyUri(id: 1));
        
        $this->getSeedFactory(['email' => 'tom@example.com'])->createOne();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Copy User')
            ->assertBodyContains('<form action="http://localhost/users" enctype="multipart/form-data" method="POST">')
            ->assertCrudFormFieldExists(field: 'email')
            ->assertBodyContains('tom@example.com');
    }
    
    public function testLiveAfterHandlerIsCalled()
    {
        $this->withCrudController(function (AppInterface $app) {
            return Factory::createCrudController(
                repository: $this->createRepository($app),
                resourceName: $this->getCrudControllerResourceName(),
                fields: [
                    new Field\PrimaryId('id'),
                    new Field\Text('firstname')
                        ->live(
                            after: function(ActionInterface $action, Field\Text $field): void {
                                $action->fields()->get('lastname')->value('Lorem');
                            },
                        ),
                    new Field\Text('lastname')
                ],
                actions: [
                    new Action\Copy('Copy User'),
                    new Action\Store(),
                ],
            );
        });
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: $this->generateCopyUri(id: 1),
            headers: ['X-Requested-With' => 'XMLHttpRequest', 'Content-Type' => 'application/json', 'X-Crud-Live' => '1'],
            body: ['firstname' => 'john'],
        );
        
        $this->getSeedFactory(['email' => 'tom@example.com'])->createOne();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('<input name="lastname" id="lastname" type="text" value="Lorem">');
    }
}