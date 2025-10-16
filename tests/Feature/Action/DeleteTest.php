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
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Storage\StorageInterface;

class DeleteTest extends \Tobento\App\Crud\Test\Feature\TestCase
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
                new Action\Delete()->undeletable(
                    [3],
                    fn (EntityInterface $entity): string => sprintf('ID %s undeletable because of...', $entity->id()),
                ),
                new Action\Index(),
            ],
        );
    }
    
    public function testRedirectsToPrevUriIfDeleteActionNotSpecified()
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
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));
        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodyContains('Action delete not found.');
    }
    
    public function testRedirectsToPrevUriIfEntityNotFound()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));
        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodyContains('Record with the ID 1 not found.');
    }
    
    public function testDeletesEntity()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->followRedirects()->assertStatus(200)->assertCrudIndexEntityCount(1);

        $this->assertSame(1, $this->getCrudRepository()->count());
    }
    
    public function testUndeletableEntitiesCannotBeDeleted()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 3));
        
        $this->getSeedFactory()->times(3)->create();
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(3)
            ->assertBodyContains('ID 3 undeletable because of...');

        $this->assertSame(3, $this->getCrudRepository()->count());
    }
}