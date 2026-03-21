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
use Tobento\App\Crud\Filter;
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
                new Action\Create(),
                new Action\BulkDelete(),
                new Action\Delete()->undeletable(
                    [3],
                    fn (EntityInterface $entity): string => sprintf('ID %s undeletable because of...', $entity->id()),
                ),
            ],
            filters: [
                new Filter\Input(name: 'email', field: 'email'),
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
            ->assertBodyContains('<form action="http://localhost/users/bulk/bulk-delete" name="bulk-delete" method="POST">')
            ->assertBodyContains('Rows to Delete')
            ->assertBodyContains('Are you sure you want to delete these items?');
    }
    
    public function testDeletesEntitiesByIdsAsDefault()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'bulk-delete'),
            body: ['ids' => ['1', '2']],
        );
        
        $this->getSeedFactory()->times(5)->create();
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(3);
    }
    
    public function testDeletesEntitiesBySelectionModeIds()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'bulk-delete'),
            body: [
                'ids' => ['1', '2'],
                'bulk-delete_selection_mode' => 'ids',
            ],
        );
        
        $this->getSeedFactory()->times(5)->create();
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(3);
    }
    
    public function testDeletesEntitiesBySelectionModeFiltered()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'bulk-delete'),
            body: [
                'bulk-delete_selection_mode' => 'filtered',
            ],
        );
        
        $this->getSeedFactory()->times(5)->create();
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(1); // because id: 3 undeletable 
    }
    
    public function testDeletesEntitiesBySelectionModeFilteredUsesFilters()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: $this->generateIndexUri(),
            query: ['filter' => ['email' => 'tim@example.com']],
            body: [
                'bulk-delete_selection_mode' => 'filtered',
            ],
        );
        
        $this->getSeedFactory(['email' => 'tom@example.com'])->times(3)->create();
        $this->getSeedFactory(['email' => 'tim@example.com'])->times(4)->create();
        
        $http->response()->assertStatus(200);
        
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'bulk-delete'),
            body: [
                'bulk-delete_selection_mode' => 'filtered',
            ],
        );
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(3);
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
            ->assertBodyContains('ID 3 undeletable because of...')
            ->assertCrudIndexEntityCount(2);
    }    
}