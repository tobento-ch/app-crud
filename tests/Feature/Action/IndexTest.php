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
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Test\Factory;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Storage\StorageInterface;

class IndexTest extends \Tobento\App\Crud\Test\Feature\TestCase
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
            ],
            actions: [
                Action\Index::new('Users'),
            ],
        );
    }

    public function testReturnsNotFoundIfIndexActionNotSpecified()
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
        $http->request(method: 'GET', uri: $this->generateIndexUri());

        $http->response()->assertStatus(404);
    }
    
    public function testIndexScreenIsRendered()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertNodeExists('h1', fn ($n): bool => $n->text() === 'Users')
            ->assertCrudIndexEntityCount(0);
    }
    
    public function testMultipleEntitiesAreRendered()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(3)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(3);
    }
    
    public function testIndexGlobalButtonsAreRendered()
    {
        $this->withCrudController(function (AppInterface $app) {
            return Factory::createCrudController(
                repository: $this->createRepository($app),
                resourceName: $this->getCrudControllerResourceName(),
                fields: [],
                actions: [
                    Action\Index::new('Users'),
                    Action\Create::new(),
                ],
            );
        });
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexButtonsExists(buttons: ['create'], group: 'global');
    }
    
    public function testFiltersAreRendered()
    {
        $this->withCrudController(function (AppInterface $app) {
            return Factory::createCrudController(
                repository: $this->createRepository($app),
                resourceName: $this->getCrudControllerResourceName(),
                fields: [],
                actions: [Action\Index::new('Users')],
                filters: [
                    Filter\Columns::new()->group('header'),
                    Filter\Select::new('colors')->group('footer')->options(['blue' => 'Blue']),
                    Filter\Select::new('roles')->group('aside')->options(['admin' => 'Admin']),
                    Filter\Select::new('cars')->group('modal')->options(['bmw' => 'Bmw']),
                ],
            );
        });
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexFiltersExists(filters: ['columns'], group: 'header')
            ->assertCrudIndexFiltersExists(filters: ['colors'], group: 'footer')
            ->assertCrudIndexFiltersExists(filters: ['roles'], group: 'aside')
            ->assertCrudIndexFiltersExists(filters: ['cars'], group: 'modal');
    }
    
    public function testEntitiesAreFiltered()
    {
        $this->withCrudController(function (AppInterface $app) {
            $fields = new Field\Fields(
                Field\Text::new('id'),
                Field\Text::new('email')->validate('string|email'),
            );
            return Factory::createCrudController(
                repository: $this->createRepository($app),
                resourceName: $this->getCrudControllerResourceName(),
                fields: $fields->all(),
                actions: [Action\Index::new('Users')],
                filters: [
                    ...Filter\Fields::new()->fields($fields)->toFilters(),
                ],
            );
        });
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: $this->generateIndexUri(),
            query: ['filter' => ['field' => ['email' => 'tom']]],
        );
        
        $this->getSeedFactory(['email' => 'tom@example.com'])->createOne();
        $this->getSeedFactory()->times(3)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(1);
    }
    
    public function testEditableColumnsAreRendered()
    {
        $this->withCrudController(function (AppInterface $app) {
            return Factory::createCrudController(
                repository: $this->createRepository($app),
                resourceName: $this->getCrudControllerResourceName(),
                fields: [
                    Field\Text::new('id'),
                    Field\Text::new('email')->validate('string|email'),
                ],
                actions: [Action\Index::new('Users')],
                filters: [
                    Filter\EditableColumns::new('email'),
                ],
            );
        });
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: $this->generateIndexUri(),
            query: ['filter' => ['editable-columns' => ['email']]],
        );
        
        $this->getSeedFactory(['email' => 'tom@example.com'])->createOne();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('<input tabindex="5" name="email" type="text" value="tom@example.com">');
    }
}