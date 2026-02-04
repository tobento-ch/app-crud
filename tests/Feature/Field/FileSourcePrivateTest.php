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

use Closure;
use Tobento\App\AppInterface;
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Boot\Crud;
use Tobento\App\Crud\Event\FileSourceDeleted;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Test\Factory;
use Tobento\App\Media\FileStorage\FileWriter;
use Tobento\App\Media\FileStorage\FileWriterInterface;
use Tobento\App\Media\Upload\UploadedFileFactoryInterface;
use Tobento\App\Media\Upload\Validator;
use Tobento\App\Media\Upload\ValidatorInterface;
use Tobento\Service\FileStorage\StorageInterface as FileStorageInterface;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Storage\StorageInterface;

class FileSourcePrivateTest extends \Tobento\App\Crud\Test\Feature\TestCase
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
                new Column\Id('id'),
                new Column\Text('title'),
                new Column\Text('filesrc'),
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
                new Field\Text('title'),
                new Field\FileSource('filesrc')
                    ->storage(name: 'uploads-private')
                    ->allowedExtensions('jpg', 'txt'),
            ],
            actions: [
                new Action\Index(),
                new Action\Create(),
                new Action\Store(),
                new Action\Edit(),
                new Action\Update(),
                new Action\Delete(),
                new Action\Copy(),
                new Action\BulkDelete(),
                new Action\Show(),
            ],
        );
    }
    
    protected function withFileSource(Closure $callback): static
    {
        $this->withCrudController(function (AppInterface $app) use ($callback) {
            $fileSource = $app->call($callback);
            return Factory::createCrudController(
                repository: $this->createRepository($app),
                resourceName: $this->getCrudControllerResourceName(),
                fields: [
                    new Field\Text('title'),
                    $fileSource,
                ],
                actions: [
                    new Action\Index(),
                    new Action\Create(),
                    new Action\Store(),
                    new Action\Edit(),
                    new Action\Update(),
                    new Action\Delete(),
                    new Action\Copy(),
                    new Action\BulkDelete(),
                    new Action\Show(),
                ],
            );
        });
        
        return $this;
    }
    
    public function testIndexActionDoesDisplayPictureIfAllowed()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc', 'filesrc')
                ->storage(name: 'uploads-private')
                ->allowedExtensions('jpg', 'txt')
                ->allowPublicPicturePreview();
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory(['filesrc' => 'img1.jpg'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-private')->write(
            path: 'img1.jpg',
            content: (string)$http->getFileFactory()->createImage('img1.jpg', 50, 50)->getStream(),
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('<picture>');
    }
    
    public function testIndexActionDoesNotDisplayPictureIfNotAllowed()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory(['filesrc' => 'img.jpg'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-private')->write(
            path: 'img.jpg',
            content: (string)$http->getFileFactory()->createImage('img.jpg', 50, 50)->getStream(),
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyNotContains('<picture>');
    }
    
    public function testEditActionDisplaysFileIfAllowed()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc', 'filesrc')
                ->storage(name: 'uploads-private')
                ->allowedExtensions('jpg', 'txt')
                ->allowPublicPicturePreview();
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['filesrc' => 'img2.jpg'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-private')->write(
            path: 'img2.jpg',
            content: (string)$http->getFileFactory()->createImage('img2.jpg', 50, 50)->getStream(),
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc')
            ->assertBodyContains('<picture>');
    }
    
    public function testEditActionDoesNotDisplayFileIfNotAllowed()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['filesrc' => 'img3.jpg'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-private')->write(
            path: 'img3.jpg',
            content: (string)$http->getFileFactory()->createImage('img3.jpg', 50, 50)->getStream(),
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc')
            ->assertBodyNotContains('<picture>');
    }
}