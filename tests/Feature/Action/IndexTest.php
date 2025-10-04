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
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
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
            ],
            actions: [
                new Action\Index('Users'),
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
                    new Action\Index('Users'),
                    new Action\Create(),
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
                actions: [new Action\Index('Users')],
                filters: [
                    new Filter\Columns()->group('header'),
                    new Filter\Select('colors')->group('footer')->options(['blue' => 'Blue']),
                    new Filter\Select('roles')->group('aside')->options(['admin' => 'Admin']),
                    new Filter\Select('cars')->group('modal')->options(['bmw' => 'Bmw']),
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
    
    public function testOnlyDisplayableFiltersAreRendered()
    {
        $this->withCrudController(function (AppInterface $app) {
            return Factory::createCrudController(
                repository: $this->createRepository($app),
                resourceName: $this->getCrudControllerResourceName(),
                fields: [],
                actions: [new Action\Index('Users')],
                filters: [
                    new Filter\Select('colors')->options(['blue' => 'Blue']),
                    new Filter\Select('roles')->options(['admin' => 'Admin'])->displayIf(false),
                    new Filter\Select('cars')->options(['bmw' => 'Bmw'])
                        ->displayIf(fn (FiltersInterface $filters, FilterInterface $filter, ActionInterface $action): bool => false),
                ],
            );
        });
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexFiltersExists(filters: ['colors'], group: 'header')
            ->assertCrudIndexFiltersMissing(filters: ['roles'], group: 'header')
            ->assertCrudIndexFiltersMissing(filters: ['cars'], group: 'header');
    }
    
    public function testEntitiesAreFiltered()
    {
        $this->withCrudController(function (AppInterface $app) {
            $fields = new Field\Fields(
                new Field\Text('id'),
                new Field\Text('email')->validate('string|email'),
            );
            return Factory::createCrudController(
                repository: $this->createRepository($app),
                resourceName: $this->getCrudControllerResourceName(),
                fields: $fields->all(),
                actions: [new Action\Index('Users')],
                filters: [
                    ...new Filter\Fields()->fields($fields)->toFilters(),
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
                    new Field\Text('id'),
                    new Field\Text('email')->validate('string|email'),
                ],
                actions: [new Action\Index('Users')],
                filters: [
                    new Filter\EditableColumns('email'),
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