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

class FileSourceTest extends \Tobento\App\Crud\Test\Feature\TestCase
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
                    ->storage(name: 'uploads-public')
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

    public function testIndexActionDisplaysFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory(['filesrc' => 'document.txt'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-public')->write(path: 'document.txt', content: 'content');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('document.txt');
    }
    
    public function testIndexActionDisplaysPictureIfFileImage()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory(['filesrc' => 'img.jpg'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-public')->write(
            path: 'img.jpg',
            content: (string)$http->getFileFactory()->createImage('img.jpg', 50, 50)->getStream(),
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('<picture>');
    }
    
    public function testIndexActionNotDisplaysFileIfNotExists()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory(['filesrc' => 'documents.txt'])->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyNotContains('documents.txt');
    }    
    
    public function testCreateAction()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCreateUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc')
            ->assertBodyContains('<input accept=".jpg,.txt" name="filesrc" id="filesrc" type="file">');
    }

    public function testStoreActionUploadsFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'filesrc' => $http->getFileFactory()->createImage('profile.jpg', 50, 50),
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads-public')->assertCreated('profile.jpg');

        $this->assertSame('profile.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testStoreActionUploadsFileRenamingFileDublicate()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'filesrc' => $http->getFileFactory()->createImage('profile.jpg', 50, 50),
        ]);
        
        $this->bootingApp();
        $fileStorage->storage(name: 'uploads-public')->write(path: 'profile.jpg', content: 'content');
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads-public')->assertCreated('profile-1.jpg');

        $this->assertSame('profile-1.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testStoreActionUploadsFileFromFileStorage()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'title' => 'foo',
            'filesrc' => ['storage' => 'uploads-public', 'path' => 'file.txt'],
        ]);
        
        $this->bootingApp();
        $fileStorage->storage(name: 'uploads-public')->write(path: 'file.txt', content: 'content');
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads-public')->assertCreated('file.txt');

        $this->assertSame('file-1.txt', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testStoreActionIgnoresWithoutFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'title' => 'foo',
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame(null, $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testStoreActionIgnoresUploadedFileWithErrorNoFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'title' => 'foo',
            'filesrc' => $http->getFileFactory()->createFile('nofile')->setError(4),
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame(null, $this->getCrudRepository()->findById(1)->get('filesrc'));
    }

    public function testStoreActionIgnoresEmptyFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'title' => 'foo',
            'filesrc' => '',
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame('', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testStoreActionFailsWithInvalidFileAndDisplaysErrorMessage()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'filesrc' => 'invalid',
        ]);
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc', errorText: 'The uploaded file is invalid.');
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame(null, $this->getCrudRepository()->findById(1));
    }
    
    public function testStoreActionFailsWithRequiredIfNoFile()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc', 'filesrc')->required();
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'title' => 'foo',
            'filesrc' => $http->getFileFactory()->createFile('nofile')->setError(4),
        ]);
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc', errorText: 'The filesrc is required.');
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame(null, $this->getCrudRepository()->findById(1));
    }
    
    public function testStoreActionFailsIfValidationFailsAndDisplaysErrorMessage()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'filesrc' => $http->getFileFactory()->createFile('file.php'),
        ]);
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc', errorText: 'The extension php of the file file.php is disallowed. Allowed extensions are jpg,txt.');
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame(null, $this->getCrudRepository()->findById(1));
    }
    
    public function testStoreActionFailsIfInvalidFileStorage()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'title' => 'foo',
            'filesrc' => ['storage' => 45, 'path' => 'file.txt'],
        ]);
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc', errorText: 'Unable to upload file.');
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame(null, $this->getCrudRepository()->findById(1));
    }
    
    public function testStoreActionFailsIfFileStorageNotExists()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'title' => 'foo',
            'filesrc' => ['storage' => 'notexists', 'path' => 'file.txt'],
        ]);
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc', errorText: 'Unable to upload the file file.txt as the file storage notexists does not exist.');
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame(null, $this->getCrudRepository()->findById(1));
    }
    
    public function testStoreActionFailsIfFileStoragePathNotExists()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'title' => 'foo',
            'filesrc' => ['storage' => 'uploads-public', 'path' => 'file.txt'],
        ]);
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc');
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame(null, $this->getCrudRepository()->findById(1));
    }
    
    public function testStoreActionUsesConfiguredStorage()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')->storage('images');
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'filesrc' => $http->getFileFactory()->createImage('profile.jpg', 50, 50),
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'images')->assertCreated('profile.jpg');

        $this->assertSame('profile.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testStoreActionUsesConfiguredFolder()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')->storage(name: 'uploads-public')->folder(path: 'products');
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'filesrc' => $http->getFileFactory()->createImage('profile.jpg', 50, 50),
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads-public')->assertCreated('products/profile.jpg');

        $this->assertSame('products/profile.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testStoreActionUsesConfiguredAllowedExtensions()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')->storage(name: 'uploads-public')->allowedExtensions('txt');
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'filesrc' => $http->getFileFactory()->createFileWithContent('file-ext.txt', 'content'),
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads-public')->assertCreated('file-ext.txt');

        $this->assertSame('file-ext.txt', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testStoreActionUsesConfiguredMaxFileSizeInKb()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')->storage(name: 'uploads-public')->maxFileSizeInKb(1000);
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'filesrc' => $http->getFileFactory()->createImage('profile.jpg')->setSize(3000),
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateCreateUri());
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc');
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame(null, $this->getCrudRepository()->findById(1));
    }
    
    public function testStoreActionUsesConfiguredValidator()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')
                ->storage(name: 'uploads-public')
                ->validator(static function(): ValidatorInterface {
                    return new Validator(
                        allowedExtensions: ['txt'],
                    );
                });
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'filesrc' => $http->getFileFactory()->createFileWithContent('file-val.txt', 'content'),
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads-public')->assertCreated('file-val.txt');

        $this->assertSame('file-val.txt', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testStoreActionUsesConfiguredFileWriter()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')
                ->storage(name: 'uploads-public')
                ->fileWriter(static function(FileStorageInterface $storage): FileWriterInterface {
                    return new FileWriter(
                        storage: $storage,
                        filenames: function (string $filename): string {
                            return 'testname';
                        },
                    );
                });
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'filesrc' => $http->getFileFactory()->createImage('profile.jpg', 50, 50),
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads-public')->assertCreated('testname.jpg');

        $this->assertSame('testname.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testStoreActionUsesConfiguredInputModifier()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')
                ->storage(name: 'uploads-public')
                ->modifyInputValue(
                    modifier: function(mixed $value, Field\FileSource $field, UploadedFileFactoryInterface $uploadedFileFactory): mixed {
                        return 'modifiedValue';
                    },
                );
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'filesrc' => $http->getFileFactory()->createImage('profile.jpg')->setSize(3000),
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateCreateUri());
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc');
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame(null, $this->getCrudRepository()->findById(1));
    }

    public function testEditActionDisplaysInputIfNoFile()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['title' => 'foo'])->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc')
            ->assertBodyContains('<input accept=".jpg,.txt" name="filesrc" id="filesrc" type="file">');
    }
    
    public function testEditActionDisplaysInputIfFileNotExists()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['filesrc' => 'profile.jpg'])->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc')
            ->assertBodyContains('<input accept=".jpg,.txt" name="filesrc" id="filesrc" type="file">');
    }
    
    public function testEditActionDisplaysFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['filesrc' => 'docu.txt'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-public')->write(path: 'docu.txt', content: 'content');
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc')
            ->assertBodyContains('docu.txt')
            ->assertBodyNotContains('<input accept=".jpg,.txt" name="filesrc" id="filesrc" type="file">')
            ->assertBodyNotContains('<picture>');
    }
    
    public function testEditActionDisplaysPictureIfImageFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['filesrc' => 'profile-edit.jpg'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-public')->write(
            path: 'profile-edit.jpg',
            content: (string)$http->getFileFactory()->createImage('profile.jpg', 50, 50)->getStream()
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc')
            ->assertBodyContains('<picture>');
    }
    
    public function testEditActionNotDisplaysPictureIfDisabled()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')->storage(name: 'uploads-public')->picture(definition: null);
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['filesrc' => 'profile-pic.jpg'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-public')->write(
            path: 'profile-pic.jpg',
            content: (string)$http->getFileFactory()->createImage('profile.jpg', 50, 50)->getStream()
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc')
            ->assertBodyContains('profile-pic.jpg')
            ->assertBodyNotContains('<picture>');
    }
    
    public function testEditActionDisplaysImageEditorIfConfiguredAndIsImageFile()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')->storage(name: 'uploads-public')->imageEditor(template: 'default');
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $app = $this->getApp();
        $app->boot(new \Tobento\App\Media\Feature\ImageEditor());
        
        $this->getSeedFactory(['filesrc' => 'editable.jpg'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-public')->write(
            path: 'editable.jpg',
            content: (string)$http->getFileFactory()->createImage('editable.jpg', 50, 50)->getStream()
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc')
            ->assertBodyContains('<a data-action="edit" href="http://localhost/media/image-editor/default/uploads-public/editable.jpg">edit</a>');
    }
    
    public function testEditActionNotDisplaysImageEditorIfNotImageFile()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')->storage(name: 'uploads-public')->imageEditor(template: 'default');
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $app = $this->getApp();
        $app->boot(new \Tobento\App\Media\Feature\ImageEditor());
        
        $this->getSeedFactory(['filesrc' => 'editable.txt'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-public')->write(
            path: 'editable.txt',
            content: 'content'
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc')
            ->assertBodyNotContains('<a data-action="edit"');
    }
    
    public function testEditActionDisplaysPictureEditorIfConfiguredAndIsImageFile()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')
                ->storage(name: 'uploads-public')
                ->pictureEditor(template: 'default', definitions: ['user']);
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $app = $this->getApp();
        $app->boot(new \Tobento\App\Media\Feature\PictureEditor());
        
        $this->getSeedFactory(['filesrc' => 'editable-pic.jpg'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-public')->write(
            path: 'editable-pic.jpg',
            content: (string)$http->getFileFactory()->createImage('editable-pic.jpg', 50, 50)->getStream()
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc')
            ->assertBodyContains('<a data-action="edit-picture" href="http://localhost/media/picture-editor/default/uploads-public/editable-pic.jpg?definitions[]=user">');
    }
    
    public function testEditActionNotDisplaysPictureEditorIfNotImageFile()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')
                ->storage(name: 'uploads-public')
                ->pictureEditor(template: 'default', definitions: ['user']);
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $app = $this->getApp();
        $app->boot(new \Tobento\App\Media\Feature\PictureEditor());
        
        $this->getSeedFactory(['filesrc' => 'editable-pic.txt'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-public')->write(
            path: 'editable-pic.txt', content: 'content');
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc')
            ->assertBodyNotContains('<a data-action="edit-picture"');
    }
    
    public function testUpdateActionUploadsFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'filesrc' => $http->getFileFactory()->createImage('profile.jpg', 50, 50),
        ]);
        
        $this->getSeedFactory(['title' => 'foo'])->times(1)->create();
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads-public')->assertCreated('profile.jpg');

        $this->assertSame('profile.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testUpdateActionUploadsFileAndDeletesOldFile()
    {
        $events = $this->fakeEvents();
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'title' => 'foo',
            'filesrc' => $http->getFileFactory()->createImage('profile.jpg', 50, 50),
        ]);
        
        $this->bootingApp();
        $this->getSeedFactory(['title' => 'foo', 'filesrc' => 'readme1.txt'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-public')->write(path: 'readme1.txt', content: 'content');
        
        $this->assertTrue($fileStorage->storage(name: 'uploads-public')->exists(path: 'readme1.txt'));
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame(1, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame('profile.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
        $this->assertFalse($fileStorage->storage(name: 'uploads-public')->exists(path: 'readme1.txt'));
        
        $events->assertDispatched(FileSourceDeleted::class, static function(FileSourceDeleted $event): bool {
            return $event->path() === 'readme1.txt';
        });
    }
    
    public function testUpdateActionUploadsFileAndDeletesGeneratedPicture()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['filesrc' => 'old-profile.jpg'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-public')->write(
            path: 'old-profile.jpg',
            content: (string)$http->getFileFactory()->createImage('old-profile.jpg', 50, 50)->getStream(),
        );
        
        $http->response()->assertStatus(200);
        
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'title' => 'foo',
            'filesrc' => $http->getFileFactory()->createImage('profile.jpg', 50, 50),
        ]);
        
        $this->assertSame(2, count($fileStorage->storage(name: 'images')->files(path: '')->all()));
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame(1, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame('profile.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
        $this->assertSame(0, count($fileStorage->storage(name: 'images')->files(path: '')->all()));
    }
    
    public function testUpdateActionUploadsFileRenamingFileDublicate()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'filesrc' => $http->getFileFactory()->createImage('profile.jpg', 50, 50),
        ]);
        
        $this->getSeedFactory(['title' => 'foo'])->times(1)->create();
        $this->bootingApp();
        $fileStorage->storage(name: 'uploads-public')->write(path: 'profile.jpg', content: 'content');
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads-public')->assertCreated('profile-1.jpg');

        $this->assertSame('profile-1.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testUpdateActionUploadsFileFromFileStorage()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'title' => 'foo',
            'filesrc' => ['storage' => 'uploads-public', 'path' => 'file-update.txt'],
        ]);
        
        $this->getSeedFactory(['title' => 'foo'])->times(1)->create();
        $this->bootingApp();
        $fileStorage->storage(name: 'uploads-public')->write(path: 'file-update.txt', content: 'content');
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads-public')->assertCreated('file-update.txt');

        $this->assertSame('file-update-1.txt', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testUpdateActionIgnoresWithoutFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'title' => 'foo',
        ]);
        
        $this->getSeedFactory(['title' => 'foo', 'filesrc' => 'profile.jpg'])->times(1)->create();
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame('profile.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testUpdateActionIgnoresUploadedFileWithErrorNoFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'title' => 'foo',
            'filesrc' => $http->getFileFactory()->createFile('nofile')->setError(4),
        ]);
        
        $this->getSeedFactory(['title' => 'foo', 'filesrc' => 'profile.jpg'])->times(1)->create();
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame('profile.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testUpdateActionWithEmptyFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'title' => 'foo',
            'filesrc' => '',
        ]);
        
        $this->getSeedFactory(['title' => 'foo', 'filesrc' => ''])->times(1)->create();
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame('', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testUpdateActionWithEmptyFileDeletesOldFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'title' => 'foo',
            'filesrc' => '',
        ]);
        
        $this->getSeedFactory(['title' => 'foo', 'filesrc' => 'readme.txt'])->times(1)->create();
        $this->bootingApp();
        $fileStorage->storage(name: 'uploads-public')->write(path: 'readme.txt', content: 'content');
        
        $this->assertSame(1, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame('', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testUpdateActionWithEmptyFiledDeletesGeneratedPicture()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['filesrc' => 'old-image.jpg'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-public')->write(
            path: 'old-image.jpg',
            content: (string)$http->getFileFactory()->createImage('old-image.jpg', 50, 50)->getStream(),
        );
        
        $http->response()->assertStatus(200);
        
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'title' => 'foo',
            'filesrc' => '',
        ]);
        
        $this->assertSame(2, count($fileStorage->storage(name: 'images')->files(path: '')->all()));
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame('', $this->getCrudRepository()->findById(1)->get('filesrc'));
        $this->assertSame(0, count($fileStorage->storage(name: 'images')->files(path: '')->all()));
    }    
    
    public function testUpdateActionFailsWithInvalidFileAndDisplaysErrorMessage()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateEditUri(id: 1));
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'filesrc' => 'invalid',
        ]);
        
        $this->getSeedFactory(['title' => 'foo', 'filesrc' => 'profile.jpg'])->times(1)->create();
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc', errorText: 'The uploaded file is invalid.');
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame('profile.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testUpdateActionFailsIfValidationFailsAndDisplaysErrorMessage()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateEditUri(id: 1));
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'filesrc' => $http->getFileFactory()->createFile('file.php'),
        ]);
        
        $this->getSeedFactory(['title' => 'foo', 'filesrc' => 'profile.jpg'])->times(1)->create();
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc', errorText: 'The extension php of the file file.php is disallowed. Allowed extensions are jpg,txt.');
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame('profile.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testUpdateActionFailsIfInvalidFileStorage()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateEditUri(id: 1));
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'title' => 'foo',
            'filesrc' => ['storage' => 45, 'path' => 'file.txt'],
        ]);
        
        $this->getSeedFactory(['title' => 'foo', 'filesrc' => 'profile.jpg'])->times(1)->create();
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc', errorText: 'Unable to upload file.');
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame('profile.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testUpdateActionFailsIfFileStorageNotExists()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateEditUri(id: 1));
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'title' => 'foo',
            'filesrc' => ['storage' => 'notexists', 'path' => 'file.txt'],
        ]);
        
        $this->getSeedFactory(['title' => 'foo', 'filesrc' => 'profile.jpg'])->times(1)->create();
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc', errorText: 'Unable to upload the file file.txt as the file storage notexists does not exist.');
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame('profile.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testUpdateActionFailsIfFileStoragePathNotExists()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateEditUri(id: 1));
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'title' => 'foo',
            'filesrc' => ['storage' => 'uploads-public', 'path' => 'file.txt'],
        ]);
        
        $this->getSeedFactory(['title' => 'foo', 'filesrc' => 'profile.jpg'])->times(1)->create();
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc');
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame('profile.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testUpdateActionUsesConfiguredStorage()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')->storage('images');
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'filesrc' => $http->getFileFactory()->createImage('profile.jpg', 50, 50),
        ]);
        
        $this->getSeedFactory(['title' => 'foo'])->times(1)->create();
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'images')->assertCreated('profile.jpg');

        $this->assertSame('profile.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testUpdateActionUsesConfiguredFolder()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')->storage(name: 'uploads-public')->folder(path: 'products');
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'filesrc' => $http->getFileFactory()->createImage('profile.jpg', 50, 50),
        ]);
        
        $this->getSeedFactory(['title' => 'foo'])->times(1)->create();
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads-public')->assertCreated('products/profile.jpg');

        $this->assertSame('products/profile.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testUpdateActionUsesConfiguredAllowedExtensions()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')->storage(name: 'uploads-public')->allowedExtensions('txt');
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'filesrc' => $http->getFileFactory()->createFileWithContent('file-ext.txt', 'content'),
        ]);
        
        $this->getSeedFactory(['title' => 'foo'])->times(1)->create();
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads-public')->assertCreated('file-ext.txt');

        $this->assertSame('file-ext.txt', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testUpdateActionUsesConfiguredMaxFileSizeInKb()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')->storage(name: 'uploads-public')->maxFileSizeInKb(1000);
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateEditUri(id: 1));
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'filesrc' => $http->getFileFactory()->createImage('profile.jpg')->setSize(3000),
        ]);
        
        $this->getSeedFactory(['title' => 'foo', 'filesrc' => ''])->times(1)->create();
        
        $http->response()->assertStatus(302)->assertLocation($this->generateEditUri(id: 1));
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc');
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertSame('', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testUpdateActionUsesConfiguredValidator()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')
                ->storage(name: 'uploads-public')
                ->validator(static function(): ValidatorInterface {
                    return new Validator(
                        allowedExtensions: ['txt'],
                    );
                });
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'filesrc' => $http->getFileFactory()->createFileWithContent('file-val.txt', 'content'),
        ]);
        
        $this->getSeedFactory(['title' => 'foo'])->times(1)->create();
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads-public')->assertCreated('file-val.txt');

        $this->assertSame('file-val.txt', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testUpdateActionUsesConfiguredFileWriter()
    {
        $this->withFileSource(function () {
            return new Field\FileSource('filesrc')
                ->storage(name: 'uploads-public')
                ->fileWriter(static function(FileStorageInterface $storage): FileWriterInterface {
                    return new FileWriter(
                        storage: $storage,
                        filenames: function (string $filename): string {
                            return 'testname';
                        },
                    );
                });
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'filesrc' => $http->getFileFactory()->createImage('profile.jpg', 50, 50),
        ]);
        
        $this->getSeedFactory(['title' => 'foo'])->times(1)->create();
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads-public')->assertCreated('testname.jpg');

        $this->assertSame('testname.jpg', $this->getCrudRepository()->findById(1)->get('filesrc'));
    }
    
    public function testDeleteActionDeletesFile()
    {
        $events = $this->fakeEvents();
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));
        
        $this->getSeedFactory(['title' => 'foo', 'filesrc' => 'info.txt'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-public')->write(path: 'info.txt', content: 'content');
        
        $this->assertSame(1, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertNull($this->getCrudRepository()->findById(1));
        
        $events->assertDispatched(FileSourceDeleted::class, static function(FileSourceDeleted $event): bool {
            return $event->path() === 'info.txt';
        });
    }
    
    public function testDeleteActionDeletesGeneratedPicture()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['filesrc' => 'plant.jpg'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-public')->write(
            path: 'plant.jpg',
            content: (string)$http->getFileFactory()->createImage('plant.jpg', 50, 50)->getStream(),
        );
        
        $http->response()->assertStatus(200);
        
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));
        
        $this->assertSame(2, count($fileStorage->storage(name: 'images')->files(path: '')->all()));
        $this->assertSame(1, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame(0, count($fileStorage->storage(name: 'images')->files(path: '')->all()));
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertNull($this->getCrudRepository()->findById(1));
    }
    
    public function testCopyActionDisplaysFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCopyUri(id: 1));
        
        $this->getSeedFactory(['filesrc' => 'copy.txt'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-public')->write(path: 'copy.txt', content: 'content');
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc')
            ->assertBodyContains('copy.txt')
            ->assertBodyContains('<input name="filesrc[path]" type="hidden" value="copy.txt">')
            ->assertBodyContains('<input data-file accept=".jpg,.txt" disabled name="filesrc" id="filesrc" type="file">');
    }
    
    public function testCopyActionNotDisplaysFileIfNone()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCopyUri(id: 1));
        
        $this->getSeedFactory(['filesrc' => ''])->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'filesrc')
            ->assertBodyNotContains('<input name="filesrc[path]" type="hidden" value="copy.txt">')
            ->assertBodyContains('<input accept=".jpg,.txt" name="filesrc" id="filesrc" type="file">');
    }
    
    public function testBulkDeleteActionDeletesFile()
    {        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['filesrc' => 'bulkdel.jpg'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-public')->write(
            path: 'bulkdel.jpg',
            content: (string)$http->getFileFactory()->createImage('bulkdel.jpg', 50, 50)->getStream(),
        );
        
        $http->response()->assertStatus(200);
        
        $http->request(method: 'POST', uri: $this->generateBulkUri(action: 'bulk-delete'))->body([
            'ids' => [1],
        ]);
        
        $this->assertSame(2, count($fileStorage->storage(name: 'images')->files(path: '')->all()));
        $this->assertSame(1, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        
        $http->followRedirects()->assertStatus(200)->assertCrudIndexEntityCount(0);
        
        $this->assertSame(0, count($fileStorage->storage(name: 'images')->files(path: '')->all()));
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads-public')->files(path: '')->all()));
        $this->assertNull($this->getCrudRepository()->findById(1));
    }
    
    public function testShowActionDisplaysFiles()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateShowUri(id: 1));
        
        $this->getSeedFactory(['filesrc' => 'foo/prima.txt'])->times(1)->create();
        $fileStorage->storage(name: 'uploads-public')->write(path: 'foo/prima.txt', content: 'content');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('prima.txt');
    }
}