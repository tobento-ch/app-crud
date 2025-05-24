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

class FileTest extends \Tobento\App\Crud\Test\Feature\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    use \Tobento\App\Testing\FileStorage\RefreshFileStorages;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../../..');
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
                Column\Json::new('file'),
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
                Field\File::new('file', 'File')
                    ->fileSource(function(Field\FileSource $fs): void {
                        $fs->allowedExtensions('jpg', 'txt');
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
                Action\BulkDelete::new(),
                Action\Show::new(),
            ],
        );
    }
    
    protected function withFile(Closure $callback): static
    {
        $this->withCrudController(function (AppInterface $app) use ($callback) {
            $file = $app->call($callback);
            return Factory::createCrudController(
                repository: $this->createRepository($app),
                resourceName: $this->getCrudControllerResourceName(),
                fields: [
                    Field\Text::new('title'),
                    $file,
                ],
                actions: [
                    Action\Index::new(),
                    Action\Create::new(),
                    Action\Store::new(),
                    Action\Edit::new(),
                    Action\Update::new(),
                    Action\Delete::new(),
                    Action\Copy::new(),
                    Action\BulkDelete::new(),
                    Action\Show::new(),
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
        
        $this->getSeedFactory(['file' => ['src' => 'f-document.txt']])->times(1)->create();
        $fileStorage->storage(name: 'uploads')->write(path: 'f-document.txt', content: 'content');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('f-document.txt');
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
    
    public function testCreateAction()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCreateUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'file')
            ->assertBodyContains('<input accept=".jpg,.txt" name="file[src]" id="file_src" type="file">')
            ->assertBodyNotContains('<input name="file[desc]" id="file_desc" type="text">');
    }
    
    public function testCreateActionTranslatable()
    {
        $this->withFile(function () {
            return Field\File::new('file')->translatable();
        });
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCreateUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'file')
            ->assertBodyContains('<input accept=".jpg,.png,.gif,.webp" name="file[src][en]" id="file_src_en" type="file">');
    }
    
    public function testStoreActionUploadsFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'file' => ['src' => $http->getFileFactory()->createImage('f-profile.jpg', 50, 50)],
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads')->assertCreated('f-profile.jpg');

        $this->assertSame('f-profile.jpg', $this->getCrudRepository()->findById(1)->get('file.src'));
        $this->assertSame('uploads', $this->getCrudRepository()->findById(1)->get('file.storage'));
    }
    
    public function testStoreActionWithoutFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            ['title' => 'foo'],
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads')->files(path: '')->all()));

        $this->assertSame([], $this->getCrudRepository()->findById(1)->get('file'));
    }
    
    public function testStoreActionUploadsFileTranslatable()
    {
        $this->withFile(function () {
            return Field\File::new('file')->translatable();
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'file' => ['src' => [
                'en' => $http->getFileFactory()->createImage('f-profile.jpg', 50, 50),
                'de' => $http->getFileFactory()->createImage('f-profile-de.jpg', 50, 50),
                'invalid' => $http->getFileFactory()->createImage('f-profile-de.jpg', 50, 50)
            ]],
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads')->assertCreated('f-profile.jpg');

        $this->assertSame('f-profile.jpg', $this->getCrudRepository()->findById(1)->get('file.src.en'));
        $this->assertSame('f-profile-de.jpg', $this->getCrudRepository()->findById(1)->get('file.src.de'));
        $this->assertSame(null, $this->getCrudRepository()->findById(1)->get('file.src.invalid'));
        $this->assertSame('uploads', $this->getCrudRepository()->findById(1)->get('file.storage'));
    }
    
    public function testStoreActionUploadsFileUsesStoreFilenameTo()
    {
        $this->withFile(function () {
            return Field\File::new('file')
                ->fields(
                    Field\Text::new('alt', 'Alt Text'),
                )
                ->storeFilenameTo(field: 'alt');
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'file' => ['src' => $http->getFileFactory()->createImage('foo_bar-baz.jpg', 50, 50)],
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads')->assertCreated('foo_bar-baz.jpg');

        $this->assertSame('foo bar baz', $this->getCrudRepository()->findById(1)->get('file.alt'));
    }
    
    public function testStoreActionUploadsFileUsesStoreFilenameToWithModify()
    {
        $this->withFile(function () {
            return Field\File::new('file')
                ->fields(
                    Field\Text::new('alt', 'Alt Text'),
                )
                ->storeFilenameTo(field: 'alt', modify: static function(string $filename): string {
                    return 'custom';
                });
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'file' => ['src' => $http->getFileFactory()->createImage('f-foo.jpg', 50, 50)],
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads')->assertCreated('f-foo.jpg');

        $this->assertSame('custom', $this->getCrudRepository()->findById(1)->get('file.alt'));
    }
    
    public function testStoreActionUploadsFileUsesStoreFilenameToTranslatable()
    {
        $this->withFile(function () {
            return Field\File::new('file')
                ->fields(
                    Field\Text::new('alt', 'Alt Text')->translatable(),
                )
                ->storeFilenameTo(field: 'alt');
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'file' => ['src' => $http->getFileFactory()->createImage('abc.jpg', 50, 50)],
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());

        $this->assertSame(
            ['en' => 'abc', 'de' => 'abc'],
            $this->getCrudRepository()->findById(1)->get('file.alt')
        );
    }
    
    public function testStoreActionUploadsFileTranslatableUsesStoreFilenameToTranslatable()
    {
        $this->withFile(function () {
            return Field\File::new('file')
                ->fields(
                    Field\Text::new('alt', 'Alt Text')->translatable(),
                )
                ->storeFilenameTo(field: 'alt')
                ->translatable();
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'file' => ['src' => [
                'en' => $http->getFileFactory()->createImage('f-bar.jpg', 50, 50),
                'de' => $http->getFileFactory()->createImage('f-bar-de.jpg', 50, 50),
            ]],
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());

        $this->assertSame(
            ['en' => 'f bar', 'de' => 'f bar de'],
            $this->getCrudRepository()->findById(1)->get('file.alt')
        );
    }
    
    public function testStoreActionUploadsFileTranslatableUsesStoreFilenameToTranslatableFallsbackToDefault()
    {
        $this->withFile(function () {
            return Field\File::new('file')
                ->fields(
                    Field\Text::new('alt', 'Alt Text')->translatable(),
                )
                ->storeFilenameTo(field: 'alt')
                ->translatable();
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'file' => ['src' => [
                'en' => $http->getFileFactory()->createImage('f-bar.jpg', 50, 50),
            ]],
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());

        $this->assertSame(
            ['en' => 'f bar', 'de' => 'f bar'],
            $this->getCrudRepository()->findById(1)->get('file.alt')
        );
    }
    
    public function testStoreActionUploadsFileTranslatableUsesStoreFilenameTo()
    {
        $this->withFile(function () {
            return Field\File::new('file')
                ->fields(
                    Field\Text::new('alt', 'Alt Text'),
                )
                ->storeFilenameTo(field: 'alt')
                ->translatable();
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'file' => ['src' => [
                'en' => $http->getFileFactory()->createImage('f-bar.jpg', 50, 50),
                'de' => $http->getFileFactory()->createImage('f-bar-de.jpg', 50, 50),
            ]],
        ]);
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());

        $this->assertSame('f bar', $this->getCrudRepository()->findById(1)->get('file.alt'));
    }
    
    public function testEditActionDisplaysInputIfNoFile()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['title' => 'foo'])->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'file')
            ->assertBodyContains('<input accept=".jpg,.txt" name="file[src]" id="file_src" type="file">');
    }
    
    public function testEditActionDisplaysInputIfFileNotExists()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['file' => ['src' => 'f-doc.txt']])->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'file')
            ->assertBodyContains('<input accept=".jpg,.txt" name="file[src]" id="file_src" type="file">');
    }
    
    public function testEditActionDisplaysFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['file' => ['src' => 'f-doc.txt']])->times(1)->create();
        $fileStorage->storage(name: 'uploads')->write(path: 'f-doc.txt', content: 'content');
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'file')
            ->assertBodyContains('f-doc.txt')
            ->assertBodyNotContains('<input data-file accept=".jpg,.txt" name="file[src]" id="file_src" type="file">')
            ->assertBodyContains('<input name="file[desc]" id="file_desc" type="text"');
    }
    
    public function testEditActionDisplaysFileTranslatable()
    {
        $this->withFile(function () {
            return Field\File::new('file')->translatable();
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(
            ['file' => ['src' =>['en' => 'f-doc-en.txt', 'de' => 'f-doc-de.txt']]]
        )->times(1)->create();
        $fileStorage->storage(name: 'uploads')->write(path: 'f-doc-en.txt', content: 'content');
        $fileStorage->storage(name: 'uploads')->write(path: 'f-doc-de.txt', content: 'content');
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'file')
            ->assertBodyContains('f-doc-en.txt')
            ->assertBodyContains('f-doc-de.txt')
            ->assertBodyNotContains('<input accept=".jpg,.png,.gif,.webp" name="file[src][en]" id="file_src_de" type="file">')
            ->assertBodyNotContains('<input accept=".jpg,.png,.gif,.webp" name="file[src][de]" id="file_src_de" type="file">');
    }
    
    public function testEditActionDisplaysFileTranslatableShowsFileInputForMissingLocale()
    {
        $this->withFile(function () {
            return Field\File::new('file')->translatable();
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(
            ['file' => ['src' =>['en' => 'f-file.txt']]]
        )->times(1)->create();
        $fileStorage->storage(name: 'uploads')->write(path: 'f-file.txt', content: 'content');
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'file')
            ->assertBodyContains('f-file.txt')
            ->assertBodyContains('<input accept=".jpg,.png,.gif,.webp" name="file[src][de]" id="file_src_de" type="file">');
    }
    
    public function testEditActionDisplaysPictureIfImageFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['file' => ['src' => 'f-edit.jpg']])->times(1)->create();
        $fileStorage->storage(name: 'uploads')->write(
            path: 'f-edit.jpg',
            content: (string)$http->getFileFactory()->createImage('f-edit.jpg', 50, 50)->getStream()
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'file')
            ->assertBodyContains('<picture>');
    }
    
    public function testUpdateActionUploadsFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'file' => ['src' => $http->getFileFactory()->createImage('f-profile.jpg', 50, 50)],
        ]);
        
        $this->getSeedFactory(['title' => 'foo'])->times(1)->create();
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads')->assertCreated('f-profile.jpg');

        $this->assertSame('f-profile.jpg', $this->getCrudRepository()->findById(1)->get('file.src'));
        $this->assertSame('uploads', $this->getCrudRepository()->findById(1)->get('file.storage'));
    }
    
    public function testUpdateActionUploadsFileTranslatable()
    {
        $this->withFile(function () {
            return Field\File::new('file')->translatable();
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'file' => ['src' => [
                'en' => $http->getFileFactory()->createImage('f-bar.jpg', 50, 50),
                'de' => $http->getFileFactory()->createImage('f-bar-de.jpg', 50, 50),
                'invalid' => $http->getFileFactory()->createImage('f-bar-de.jpg', 50, 50),
            ]],
        ]);
        
        $this->getSeedFactory(['title' => 'foo'])->times(1)->create();
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $fileStorage->storage(name: 'uploads')->assertCreated('f-bar.jpg');
        $fileStorage->storage(name: 'uploads')->assertCreated('f-bar-de.jpg');

        $this->assertSame('f-bar.jpg', $this->getCrudRepository()->findById(1)->get('file.src.en'));
        $this->assertSame('f-bar-de.jpg', $this->getCrudRepository()->findById(1)->get('file.src.de'));
        $this->assertSame(null, $this->getCrudRepository()->findById(1)->get('file.src.invalid'));
        $this->assertSame('uploads', $this->getCrudRepository()->findById(1)->get('file.storage'));
    }    
    
    public function testUpdateActionDeletesFileIfSrcIsEmpty()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'file' => ['src' => ''],
        ]);
        
        $this->getSeedFactory(['file' => ['src' => 'f-readme.txt']])->times(1)->create();
        $fileStorage->storage(name: 'uploads')->write(path: 'f-readme.txt', content: 'content');
        
        $this->assertTrue($fileStorage->storage(name: 'uploads')->exists(path: 'f-readme.txt'));
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame([], $this->getCrudRepository()->findById(1)->get('file'));
        $this->assertFalse($fileStorage->storage(name: 'uploads')->exists(path: 'f-readme.txt'));
    }
    
    public function testDeleteActionDeletesFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['file' => ['src' => 'f-plant.jpg']])->times(1)->create();
        $fileStorage->storage(name: 'uploads')->write(
            path: 'f-plant.jpg',
            content: (string)$http->getFileFactory()->createImage('f-plant.jpg', 50, 50)->getStream(),
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
    
    public function testDeleteActionDeletesFileTranslatable()
    {
        $this->withFile(function () {
            return Field\File::new('file')->translatable();
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(
            ['file' => ['src' => ['en' => 'f-plant-en.jpg', 'de' => 'f-plant-de.jpg']]]
        )->times(1)->create();
        $fileStorage->storage(name: 'uploads')->write(
            path: 'f-plant-en.jpg',
            content: (string)$http->getFileFactory()->createImage('f-plant-en.jpg', 50, 50)->getStream(),
        );
        $fileStorage->storage(name: 'uploads')->write(
            path: 'f-plant-de.jpg',
            content: (string)$http->getFileFactory()->createImage('f-plant-de.jpg', 50, 50)->getStream(),
        );
        
        $http->response()->assertStatus(200);
        
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));
        
        $this->assertSame(4, count($fileStorage->storage(name: 'images')->files(path: '')->all()));
        $this->assertSame(2, count($fileStorage->storage(name: 'uploads')->files(path: '')->all()));
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        
        $this->assertSame(0, count($fileStorage->storage(name: 'images')->files(path: '')->all()));
        $this->assertSame(0, count($fileStorage->storage(name: 'uploads')->files(path: '')->all()));
        $this->assertNull($this->getCrudRepository()->findById(1));
    }    
    
    public function testCopyActionDisplaysFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCopyUri(id: 1));
        
        $this->getSeedFactory(['file' => ['src' => 'f-fcopy.txt']])->times(1)->create();
        $fileStorage->storage(name: 'uploads')->write(path: 'f-fcopy.txt', content: 'content');
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'file')
            ->assertBodyContains('f-fcopy.txt')
            ->assertBodyContains('<input name="file[src][path]" type="hidden" value="f-fcopy.txt">')
            ->assertBodyContains('<input data-file accept=".jpg,.txt" disabled name="file[src]" id="file_src" type="file">')
            ->assertBodyContains('<input name="file[desc]" id="file_desc" type="text"');
    }
    
    public function testCopyActionNotDisplaysFileIfNone()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCopyUri(id: 1));
        
        $this->getSeedFactory(['file' => ['src' => '']])->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'file')
            ->assertBodyNotContains('<input name="file[src][path]" type="hidden" value="copy.txt">')
            ->assertBodyContains('<input accept=".jpg,.txt" name="file[src]" id="file_src" type="file">');
    }
    
    public function testBulkDeleteActionDeletesFile()
    {        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['file' => ['src' => 'f-bulkdel.jpg']])->times(1)->create();
        $fileStorage->storage(name: 'uploads')->write(
            path: 'f-bulkdel.jpg',
            content: (string)$http->getFileFactory()->createImage('f-bulkdel.jpg', 50, 50)->getStream(),
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
    
    public function testShowActionDisplaysFiles()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateShowUri(id: 1));
        
        $this->getSeedFactory(['file' => ['src' => 'f-tree.txt']])->times(1)->create();
        $fileStorage->storage(name: 'uploads')->write(path: 'f-tree.txt', content: 'content');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('File')
            ->assertBodyContains('f-tree.txt')
            ->assertBodyContains('Desc');
    }
}