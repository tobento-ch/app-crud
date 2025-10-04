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

class BulkDeleteTest extends \Tobento\App\Crud\Test\Feature\TestCase
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
            ],
            actions: [
                new Action\Index('Users'),
                new Action\BulkDelete(),
                new Action\Delete()->undeletable([3]),
            ],
        );
    }
    
    public function testBulkDeleteIsRendered()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(3)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('<form action="http://localhost/users/bulk/bulk-delete" method="POST">')
            ->assertBodyContains('Are you sure you want to delete all selected items?');
    }
    
    public function testDeletesEntities()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'bulk-delete'),
            body: ['ids' => ['1', '2', '12']],
        );
        
        $this->getSeedFactory()->times(3)->create();
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(1);
    }
    
    public function testUndeletableEntitiesAreIgnored()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'bulk-delete'),
            body: ['ids' => ['1', '3', '12']],
        );
        
        $this->getSeedFactory()->times(3)->create();
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(2);
    }    
}