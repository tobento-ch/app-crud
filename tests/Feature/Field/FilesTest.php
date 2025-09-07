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
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Test\Factory;
use Tobento\Service\FileStorage\StorageInterface as FileStorageInterface;
use Tobento\Service\Language\LanguageFactory;
use Tobento\Service\Language\Languages;
use Tobento\Service\Language\LanguagesInterface;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Storage\StorageInterface;

class FilesTest extends \Tobento\App\Crud\Test\Feature\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    use \Tobento\App\Testing\FileStorage\RefreshFileStorages;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../../..');
        $app->boot(\Tobento\App\Boot\ErrorHandling::class);
        $app->booting();
        $app->get(\Tobento\Service\Config\ConfigInterface::class)->set('app.debug', true);
        
        $app->boot(Crud::class);
        
        $app->on(LanguagesInterface::class, function() {
            $languageFactory = new LanguageFactory();
            return new Languages(
                $languageFactory->createLanguage(locale: 'en', default: true),
                $languageFactory->createLanguage(locale: 'de', slug: 'de'),
            );
        });
        
        return $app;
    }

    protected function createRepository(AppInterface $app): RepositoryInterface
    {
        return Factory::createStorageRepository(
            table: 'users',
            columns: [
                Column\Id::new('id'),
                Column\Text::new('title'),
                Column\Json::new('files'),
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
                Field\Text::new('title'),
                Field\Files::new('files')
                    ->file(function(Field\File $file): void {
                        $file->fileSource(function(Field\FileSource $fs): void {
                            $fs->allowedExtensions('jpg', 'txt');
                        });
                    })
                    ->fields(
                        Field\Text::new('desc', 'Desc'),
                    ),
            ],
            actions: [
                Action\Index::new(),
                Action\Create::new(),
                Action\Store::new(),
                Action\Edit::new(),
                Action\Update::new(),
                Action\Delete::new(),
                Action\Copy::new(),
                Action\Show::new(),
                Action\BulkDelete::new(),
            ],
        );
    }
    
    protected function withFile(Closure $callback): static
    {
        $this->withCrudController(function (AppInterface $app) use ($callback) {
            $files = $app->call($callback);
            return Factory::createCrudController(
                repository: $this->createRepository($app),
                resourceName: $this->getCrudControllerResourceName(),
                fields: [
                    Field\Text::new('title'),
                    $files,
                ],
                actions: [
                    Action\Index::new(),
                    Action\Create::new(),
                    Action\Store::new(),
                    Action\Edit::new(),
                    Action\Update::new(),
                    Action\Delete::new(),
                    Action\Copy::new(),
                    Action\Show::new(),
                    Action\BulkDelete::new(),
                ],
            );
        });
        
        return $this;
    }

    public function testIndexActionDisplaysFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory([
            'files' => [['src' => 'fsdoc1.txt'], ['src' => 'fsdoc2.txt']]
        ])->times(1)->create();
        $fileStorage->storage(name: 'uploads')->write(path: 'fsdoc1.txt', content: 'content');
        $fileStorage->storage(name: 'uploads')->write(path: 'fsdoc2.txt', content: 'content');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('fsdoc1.txt');
    }
    
    public function testIndexActionNotDisplaysFileIfNone()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory(['title' => 'foo'])->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyNotContains('document.txt');
    }
    
    public function testCreateActionDisplaysInputOnly()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCreateUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'files')
            ->assertBodyContains('<input multiple accept=".jpg,.txt" name="files[src][]" id="files_src_" type="file"')
            ->assertBodyNotContains('<input name="files[0][desc]" id="files_0_desc" type="text"');
    }
    
    public function testStoreActionUploadsFiles()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'files' => [
                ['src' => $http->getFileFactory()->createImage('fs-profile.jpg', 50, 50)],
                ['src' => $http->getFileFactory()->createImage('fs-profile1.jpg', 50, 50)],
            ],
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads')->assertCreated('fs-profile.jpg');
        $fileStorage->storage(name: 'uploads')->assertCreated('fs-profile1.jpg');

        $this->assertSame('fs-profile.jpg', $this->getCrudRepository()->findById(1)->get('files.0.src'));
        $this->assertSame('fs-profile1.jpg', $this->getCrudRepository()->findById(1)->get('files.1.src'));
        $this->assertSame('uploads', $this->getCrudRepository()->findById(1)->get('files.0.storage'));
    }
    
    public function testStoreActionWithoutFiles()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'title' => 'foo'
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads')->files(path: '')->all()));
        $this->assertSame([], $this->getCrudRepository()->findById(1)->get('files'));
    }
    
    public function testStoreActionUploadsFilesUsesStoreFilenameTo()
    {
        $this->withFile(function () {
            return Field\Files::new('files')
                ->file(function(Field\File $file): void {
                    $file->storeFilenameTo(field: 'alt');
                })
                ->fields(
                    Field\Text::new('alt', 'Alt Text'),
                );
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'files' => [['src' => $http->getFileFactory()->createImage('fs-profile.jpg', 50, 50)]],
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());

        $this->assertSame('fs profile', $this->getCrudRepository()->findById(1)->get('files.0.alt'));
    }
    
    public function testEditActionDisplaysFiles()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory([
            'files' => [['src' => 'fs-docu.txt'], ['src' => 'fs-docu1.txt']]
        ])->times(1)->create();
        $fileStorage->storage(name: 'uploads')->write(path: 'fs-docu.txt', content: 'content');
        $fileStorage->storage(name: 'uploads')->write(path: 'fs-docu1.txt', content: 'content');
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'files')
            ->assertBodyContains('fs-docu.txt')
            ->assertBodyContains('fs-docu1.txt')
            ->assertBodyContains('<input multiple accept=".jpg,.txt" name="files[src][]" id="files_src_" type="file"')
            ->assertBodyContains('<input name="files[0][desc]" id="files_0_desc" type="text"')
            ->assertBodyContains('<input name="files[1][desc]" id="files_1_desc" type="text"');
    }
    
    public function testEditActionDisplaysWithoutFiles()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['title' => 'foo'])->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'files')
            ->assertBodyNotContains('docu.txt')
            ->assertBodyContains('<input multiple accept=".jpg,.txt" name="files[src][]" id="files_src_" type="file"');
    }
    
    public function testUpdateActionUploadsFiles()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'files' => [
                ['src' => $http->getFileFactory()->createImage('fs-profile.jpg', 50, 50)],
                ['src' => $http->getFileFactory()->createImage('fs-profile1.jpg', 50, 50)],
            ],
        ]);
        
        $this->getSeedFactory(['title' => 'foo'])->times(1)->create();
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads')->assertCreated('fs-profile.jpg');
        $fileStorage->storage(name: 'uploads')->assertCreated('fs-profile1.jpg');

        $this->assertSame('fs-profile.jpg', $this->getCrudRepository()->findById(1)->get('files.0.src'));
        $this->assertSame('fs-profile1.jpg', $this->getCrudRepository()->findById(1)->get('files.1.src'));
        $this->assertSame('uploads', $this->getCrudRepository()->findById(1)->get('files.0.storage'));
    }
    
    public function testUpdateActionUploadsFilesMergesExistingFiles()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'files' => [
                ['order' => 0],
                ['src' => $http->getFileFactory()->createImage('fs-profile.jpg', 50, 50)],
                ['src' => $http->getFileFactory()->createImage('fs-profile1.jpg', 50, 50)],
            ],
        ]);
        
        $this->getSeedFactory([
            'files' => [['src' => 'fs-profile5.jpg']]
        ])->times(1)->create();
        $fileStorage->storage(name: 'uploads')->write(path: 'fs-profile5.jpg', content: 'content');
        $this->assertTrue($fileStorage->storage(name: 'uploads')->exists(path: 'fs-profile5.jpg'));
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads')->assertCreated('fs-profile.jpg');
        $fileStorage->storage(name: 'uploads')->assertCreated('fs-profile1.jpg');
        
        $this->assertSame('fs-profile5.jpg', $this->getCrudRepository()->findById(1)->get('files.0.src'));
        $this->assertSame('fs-profile.jpg', $this->getCrudRepository()->findById(1)->get('files.1.src'));
        $this->assertSame('fs-profile1.jpg', $this->getCrudRepository()->findById(1)->get('files.2.src'));
        $this->assertSame('uploads', $this->getCrudRepository()->findById(1)->get('files.0.storage'));
    }
    
    public function testDeleteActionDeletesFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory([
            'files' => [['src' => 'fs-pic.jpg']]
        ])->times(1)->create();
        
        $fileStorage->storage(name: 'uploads')->write(
            path: 'fs-pic.jpg',
            content: (string)$http->getFileFactory()->createImage('fs-pic.jpg', 50, 50)->getStream(),
        );

        $http->response()->assertStatus(200);
        
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));
        
        $this->assertSame(2, count($fileStorage->storage(name: 'images')->files(path: '')->all()));
        $this->assertSame(1, count($fileStorage->storage(name: 'uploads')->files(path: '')->all()));
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame(0, count($fileStorage->storage(name: 'images')->files(path: '')->all()));
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads')->files(path: '')->all()));
        $this->assertNull($this->getCrudRepository()->findById(1));
    }
    
    public function testBulkDeleteActionDeletesFile()
    {        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory([
            'files' => [['src' => 'files-pic.jpg']]
        ])->times(1)->create();
        
        $fileStorage->storage(name: 'uploads')->write(
            path: 'files-pic.jpg',
            content: (string)$http->getFileFactory()->createImage('files-pic.jpg', 50, 50)->getStream(),
        );
        
        $http->response()->assertStatus(200);
        
        $http->request(method: 'POST', uri: $this->generateBulkUri(action: 'bulk-delete'))->body([
            'ids' => [1],
        ]);
        
        $this->assertSame(2, count($fileStorage->storage(name: 'images')->files(path: '')->all()));
        $this->assertSame(1, count($fileStorage->storage(name: 'uploads')->files(path: '')->all()));
        
        $http->followRedirects()->assertStatus(200)->assertCrudIndexEntityCount(0);
        
        $this->assertSame(0, count($fileStorage->storage(name: 'images')->files(path: '')->all()));
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads')->files(path: '')->all()));
        $this->assertNull($this->getCrudRepository()->findById(1));
    }
    
    public function testCopyActionDisplaysFiles()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCopyUri(id: 1));
        
        $this->getSeedFactory(['files' => [['src' => 'fs-copy.txt']]])->times(1)->create();
        $fileStorage->storage(name: 'uploads')->write(path: 'fs-copy.txt', content: 'content');
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'files')
            ->assertBodyContains('fs-copy.txt')
            ->assertBodyContains('<input name="files[0][src][path]" type="hidden" value="fs-copy.txt">')
            ->assertBodyContains('<input multiple accept=".jpg,.txt" name="files[src][]" id="files_src_" type="file"')
            ->assertBodyContains('<input name="files[0][desc]" id="files_0_desc" type="text"');
    }
    
    public function testShowActionDisplaysFiles()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateShowUri(id: 1));
        
        $this->getSeedFactory(['files' => [['src' => 'fs-price.txt']]])->times(1)->create();
        $fileStorage->storage(name: 'uploads')->write(path: 'fs-price.txt', content: 'content');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Files')
            ->assertBodyContains('fs-price.txt')
            ->assertBodyContains('desc');
    }

    public function testUploadFilesFailsIfMinNumberOfFilesIsNotReached()
    {
        $this->withFile(function () {
            return Field\Files::new('files')
                ->numberOfFiles(min: 2);
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'files' => [
                ['src' => $http->getFileFactory()->createImage('fs-in.jpg', 50, 50)],
            ],
        ]);
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'files', errorText: 'The Files must have at least 2 items.');
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads')->files(path: '')->all()));
    }
    
    public function testUploadFilesFailsIfMaxNumberOfFilesExceeds()
    {
        $this->withFile(function () {
            return Field\Files::new('files')
                ->numberOfFiles(max: 2);
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateEditUri(id: 1));
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'files' => [
                ['src' => $http->getFileFactory()->createImage('max.jpg', 50, 50)],
                ['src' => $http->getFileFactory()->createImage('max1.jpg', 50, 50)],
                ['src' => $http->getFileFactory()->createImage('max2.jpg', 50, 50)],
            ],
        ]);
        
        $this->getSeedFactory(['title' => 'foo'])->times(1)->create();
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'files', errorText: 'The Files must have at most 2 items.');
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads')->files(path: '')->all()));
    }
}