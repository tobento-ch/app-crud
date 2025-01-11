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
                Column\Id::new(),
                Column\Text::new('email'),
                Column\Text::new('firstname'),
                Column\Text::new('lastname'),
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
                Field\PrimaryId::new('id'),
                Field\Text::new('email')->validate('string|email'),
                Field\Text::new('firstname')->validate('string'),
                Field\Text::new('lastname')->validate('string'),
            ],
            actions: [
                Action\Copy::new('Copy User'),
                Action\Store::new(),
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
}