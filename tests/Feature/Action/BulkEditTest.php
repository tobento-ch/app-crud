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

class BulkEditTest extends \Tobento\App\Crud\Test\Feature\TestCase
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
                new Field\Select('status')
                    ->options(['active', 'inactive'])
                    ->infoText(text: 'Status info create only text', action: 'create')
            ],
            actions: [
                new Action\Index('Users'),
                new Action\BulkEdit(name: 'bulk-email')->field('email'),
                new Action\BulkEdit(name: 'bulk-name')->field('firstname', 'lastname'),
                new Action\BulkEdit(name: 'bulk-status')->field('status')->fieldsFrom('create'),
                new Action\Create(),
                new Action\Update()->unupdatable(
                    [3],
                    fn (EntityInterface $entity): string => sprintf('ID %s unupdatable because of...', $entity->id())
                ),
            ],
        );
    }
    
    public function testBulkEditIsRendered()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(3)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('<form action="http://localhost/users/bulk/bulk-email" method="POST">')
            ->assertBodyContains('<input name="email" id="email" type="text" value>')
            ->assertBodyContains('<form action="http://localhost/users/bulk/bulk-name" method="POST">')
            ->assertBodyContains('<input name="firstname" id="firstname" type="text" value>')
            ->assertBodyContains('<input name="lastname" id="lastname" type="text" value>');
    }
    
    public function testUpdatesEntities()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'bulk-email'),
            body: ['ids' => ['1', '2', '12'], 'email' => 'new@example.com'],
        );
        
        $this->getSeedFactory(['email' => 'tom@example.com'])->times(3)->create();
        
        $http->followRedirects()->assertStatus(200)->assertCrudIndexEntityCount(3);
        
        $this->assertSame('new@example.com', $this->getCrudRepository()->findById(1)->get('email'));
        $this->assertSame('new@example.com', $this->getCrudRepository()->findById(2)->get('email'));
        $this->assertSame('tom@example.com', $this->getCrudRepository()->findById(3)->get('email'));
    }
    
    public function testUnupdatableEntitiesAreIgnored()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'bulk-email'),
            body: ['ids' => ['1', '3'], 'email' => 'new@example.com'],
        );
        
        $this->getSeedFactory(['email' => 'tom@example.com'])->times(3)->create();

        $http->followRedirects()->assertStatus(200)
            ->assertBodyContains('ID 3 unupdatable because of...')
            ->assertCrudIndexEntityCount(3);
        
        $this->assertSame('new@example.com', $this->getCrudRepository()->findById(1)->get('email'));
        $this->assertSame('tom@example.com', $this->getCrudRepository()->findById(2)->get('email'));
        $this->assertSame('tom@example.com', $this->getCrudRepository()->findById(3)->get('email'));
    }
    
    public function testUpdatesEntitiesWithoutDataWillBeIgnored()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'bulk-email'),
            body: ['ids' => ['1', '3']],
        );
        
        $this->getSeedFactory(['email' => 'tom@example.com'])->times(1)->create();
        
        $http->followRedirects()->assertStatus(200)->assertCrudIndexEntityCount(1);
        
        $this->assertSame('tom@example.com', $this->getCrudRepository()->findById(1)->get('email'));
    }
    
    public function testBulkEditUsesCreateFields()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());

        $this->getSeedFactory()->times(1)->create();

        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Status info create only text');
    }
}