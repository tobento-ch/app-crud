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

namespace Tobento\App\Crud\Test\Feature\Field;

use Tobento\App\AppInterface;
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Boot\Crud;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Test\Factory;
use Tobento\App\Slugging\SlugRepositoryInterface;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Storage\StorageInterface;

class SlugTest extends \Tobento\App\Crud\Test\Feature\TestCase
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
                new Column\Translatable('title'),
                new Column\Translatable('slug'),
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
                new Field\Text('id'),
                new Field\Text('title')->translatable(),
                new Field\Slug('slug')->translatable(),
            ],
            actions: [
                new Action\Index(),
                new Action\Create(),
                new Action\Store(),
                new Action\Edit(),
                new Action\Update(),
                new Action\Delete(),
            ],
        );
    }
    
    public function testEditAction()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['slug' => ['en' => 'lorem']])->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'slug');
    }

    public function testStoreAction()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'slug' => ['en' => 'Foo bar'],
        ]);

        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());

        $this->assertSame(['en' => 'foo-bar'], $this->getCrudRepository()->findById(1)->get('slug')->all());
        
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'slug' => ['en' => 'Foo bar'],
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame(['en' => 'foo-bar-1'], $this->getCrudRepository()->findById(2)->get('slug')->all());
    }
    
    public function testUpdateAction()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'slug' => ['en' => 'Foo bar'],
        ]);
        
        $this->getSeedFactory(['slug' => ['en' => 'lorem']])->times(1)->create();

        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());

        $this->assertSame(['en' => 'foo-bar'], $this->getCrudRepository()->findById(1)->get('slug')->all());
    }

    public function testSlugsAreStoredInSlugRepo()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'slug' => ['en' => 'foo-bar'],
        ]);

        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $app = $this->getApp();
        
        $this->assertSame(
            [
                1 => [
                    'slug' => 'foo-bar',
                    'locale' => 'en',
                    'resource_key' => 'users',
                    'resource_id' => '1',
                    'id' => 1,
                ],
            ],
            $app->get(SlugRepositoryInterface::class)->findAll()->toArray()
        );
    }
    
    public function testSlugsAreStoredOnceInSlugRepo()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'slug' => ['en' => 'Foo bar'],
        ]);
        
        $this->getSeedFactory(['slug' => ['en' => 'lorem']])->times(1)->create();

        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'slug' => ['en' => 'foo-bar'],
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $app = $this->getApp();
        
        $this->assertSame(
            [
                1 => [
                    'slug' => 'foo-bar',
                    'locale' => 'en',
                    'resource_key' => 'users',
                    'resource_id' => '1',
                    'id' => 1,
                ],
            ],
            $app->get(SlugRepositoryInterface::class)->findAll()->toArray()
        );
    }
    
    public function testSlugsAreUpdatedAndDeletedInSlugRepo()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'slug' => ['en' => 'lorem'],
        ]);

        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'slug' => ['en' => 'Foo Bar'],
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $app = $this->getApp();
        
        $this->assertSame(
            [
                1 => [
                    'slug' => 'foo-bar',
                    'locale' => 'en',
                    'resource_key' => 'users',
                    'resource_id' => '1',
                    'id' => 1,
                ],
            ],
            $app->get(SlugRepositoryInterface::class)->findAll()->toArray()
        );
    }
    
    public function testSlugsAreDeletedFromSlugRepo()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'slug' => ['en' => 'Foo bar'],
        ]);
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $app = $this->getApp();
        
        $this->assertSame(1, $app->get(SlugRepositoryInterface::class)->count());
        
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $app = $this->getApp();
        
        $this->assertSame(0, $app->get(SlugRepositoryInterface::class)->count());
    }
}