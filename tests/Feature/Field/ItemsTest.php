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
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Storage\StorageInterface;

class ItemsTest extends \Tobento\App\Crud\Test\Feature\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    use \Tobento\App\Testing\FileStorage\RefreshFileStorages;
    
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
                new Column\Json('items'),
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
                new Field\Items('items')->fields(
                    new Field\Text('price')->type('number')->validate('decimal'),
                    new Field\FileSource('filesrc')->allowedExtensions('jpg', 'txt'),
                ),
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
        
        $this->getSeedFactory(['items' => [1 => ['price' => '7.5']]])->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'items.1.price');
    }
    
    public function testUpdateAction()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'items' => [1 => ['price' => '7.5']],
        ]);
        
        $this->getSeedFactory(['items' => [1 => ['price' => '2.5']]])->times(1)->create();

        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());

        $this->assertSame([1 => ['price' => '7.5']], $this->getCrudRepository()->findById(1)->get('items'));
    }
    
    public function testUpdateActionDeletesItems()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'items' => [],
        ]);
        
        $this->getSeedFactory(['items' => [1 => ['price' => '2.5', 'filesrc' => 'item1.txt']]])->times(1)->create();
        $fileStorage->storage(name: 'uploads')->write(path: 'item1.txt', content: 'content');
        
        $this->assertTrue($fileStorage->storage(name: 'uploads')->exists(path: 'item1.txt'));
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());

        $this->assertSame([], $this->getCrudRepository()->findById(1)->get('items'));
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads')->files(path: '')->all()));
        $this->assertFalse($fileStorage->storage(name: 'uploads')->exists(path: 'item1.txt'));
    }
    
    public function testUpdateActionDeletesEmptyItems()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'items' => [
                1 => ['price' => '1.7'],
                2 => '',
                3 => ['price' => '3.7']
            ],
        ]);
        
        $this->getSeedFactory([
            'items' => [
                1 => ['price' => '1.5'],
                2 => ['price' => '2.5', 'filesrc' => 'item2.txt'],
                3 => ['price' => '3.5'],
            ]
        ])->times(1)->create();
        
        $fileStorage->storage(name: 'uploads')->write(path: 'item2.txt', content: 'content');
        
        $this->assertTrue($fileStorage->storage(name: 'uploads')->exists(path: 'item2.txt'));

        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());

        $this->assertSame(
            [1 => ['price' => '1.7'], 2 => ['price' => '3.7']],
            $this->getCrudRepository()->findById(1)->get('items'));
        
        $this->assertFalse($fileStorage->storage(name: 'uploads')->exists(path: 'item2.txt'));
    }
    
    public function testUpdateActionDeletesItemsIfNotArray()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'items' => 'fff',
        ]);
        
        $this->getSeedFactory(['items' => [1 => ['price' => '2.5']]])->times(1)->create();

        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());

        $this->assertSame([], $this->getCrudRepository()->findById(1)->get('items'));
    }    
    
    public function testUpdateActionWithValidationErrors()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateEditUri(id: 1));
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'items' => [1 => ['price' => 'foo']],
        ]);
        
        $this->getSeedFactory(['items' => [1 => ['price' => '7.5']]])->times(1)->create();

        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'items.1.price', errorText: 'The items.1.price must be decimal.');

        $this->assertSame([1 => ['price' => '7.5']], $this->getCrudRepository()->findById(1)->get('items'));
    }
    
    public function testUpdateActionWithMinItemsValidation()
    {
        $this->withCrudController(function (AppInterface $app) {
            return Factory::createCrudController(
                repository: $this->createRepository($app),
                resourceName: $this->getCrudControllerResourceName(),
                fields: [
                    new Field\Text('id'),
                    new Field\Items('items')->fields(
                        new Field\Text('price')->type('number')->validate('decimal'),
                    )->validate('required|minItems:2'),
                ],
                actions: [
                    new Action\Edit(),
                    new Action\Update(),
                ],
            );
        });
        
        $http = $this->fakeHttp();
        $http->previousUri($this->generateEditUri(id: 1));
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'items' => [1 => ['price' => '2.5']],
        ]);
        
        $this->getSeedFactory(['items' => [1 => ['price' => '7.5']]])->times(1)->create();

        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'items', errorText: 'The items must have at least 2 items.');

        $this->assertSame([1 => ['price' => '7.5']], $this->getCrudRepository()->findById(1)->get('items'));
    }
}