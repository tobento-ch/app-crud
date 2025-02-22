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
                Column\Id::new(),
                Column\Text::new('email'),
                Column\Text::new('firstname'),
                Column\Text::new('lastname'),
                Column\Json::new('options'),
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
                Field\Text::new('options.color'),
            ],
            actions: [
                Action\Update::new(),
                Action\Edit::new(),
                Action\Index::new(),
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
                actions: [Action\Index::new()],
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
}