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

use Psr\Http\Message\StreamInterface;
use Tobento\App\AppInterface;
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Boot\Crud;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Test\Factory;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Storage\StorageInterface;

class BulkDownloadZipTest extends \Tobento\App\Crud\Test\Feature\TestCase
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
                new Column\Text('email'),
                new Column\Text('file_source'),
                new Column\Json('file'),
                new Column\Json('files'),
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
                new Field\FileSource('file_source')->storage(name: 'uploads-public'),
                new Field\File('file'),
                new Field\Files('files'),
            ],
            actions: [
                new Action\Index('Users'),
                new Action\Create(),
                new Action\BulkDownloadZip(),
            ],
            filters: [
                new Filter\Input(name: 'email', field: 'email'),
            ],
        );
    }
    
    protected function withZipAction(\Closure $callback): static
    {
        $this->withCrudController(function (AppInterface $app) use ($callback) {
            $action = $app->call($callback);
            return Factory::createCrudController(
                repository: $this->createRepository($app),
                resourceName: $this->getCrudControllerResourceName(),
                fields: [
                    new Field\PrimaryId('id'),
                    new Field\Text('email')->validate('string|email'),
                    new Field\FileSource('file_source')->storage(name: 'uploads-public'),
                    new Field\File('file'),
                    new Field\Files('files'),
                ],
                actions: [
                    new Action\Index('Users'),
                    new Action\Create(),
                    $action,
                ],
            );
        });
        
        return $this;
    }
    
    protected function getSeedDefinition(): null|\Closure
    {
        return function (): array {
            return [
                'email' => 'tom@example.com',
                'file_source' => '',
                'file' => [],
                'files' => [],
            ];
        };
    }
    
    protected function assertZipContains(string|StreamInterface $zipBinary, array $expectedFiles): void
    {
        $zipBinary = (string)$zipBinary;
        
        // Create a temporary file to load the ZIP
        $tmp = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($tmp, $zipBinary);

        $zip = new \ZipArchive();
        $open = $zip->open($tmp);

        $this->assertTrue($open === true, 'Failed to open ZIP archive');

        foreach ($expectedFiles as $file) {
            $this->assertNotFalse(
                $zip->locateName($file),
                sprintf(
                    'ZIP does not contain expected file: %s',
                    $file
                )
            );
        }

        $zip->close();
        unlink($tmp);
    }
    
    protected function assertZipNotContains(string|StreamInterface $zipBinary, array $unexpectedFiles): void
    {
        $zipBinary = (string)$zipBinary;

        $tmp = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($tmp, $zipBinary);

        $zip = new \ZipArchive();
        $open = $zip->open($tmp);

        $this->assertTrue($open === true, 'Failed to open ZIP archive');

        foreach ($unexpectedFiles as $file) {
            $this->assertFalse(
                $zip->locateName($file),
                sprintf(
                    'ZIP unexpectedly contains file: %s',
                    $file
                )
            );
        }

        $zip->close();
        unlink($tmp);
    }

    protected function assertZipFileCount(string|StreamInterface $zipBinary, int $expectedCount): void
    {
        $zipBinary = (string)$zipBinary;

        $tmp = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($tmp, $zipBinary);

        $zip = new \ZipArchive();
        $open = $zip->open($tmp);

        $this->assertTrue($open === true, 'Failed to open ZIP archive');

        $this->assertSame(
            $expectedCount,
            $zip->numFiles,
            sprintf(
                'ZIP file count mismatch. Expected %d, got %d',
                $expectedCount,
                $zip->numFiles
            )
        );

        $zip->close();
        unlink($tmp);
    }
    
    public function testModalIsRendered()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('<form action="http://localhost/users/bulk/download-zip" name="download-zip" method="POST">')
            ->assertBodyContains('Records to ZIP')
            ->assertBodyContains('Preserve Folder Structure')
            ->assertBodyContains('Only Public Storages')
            ->assertBodyContains('Only Extensions')
            ->assertBodyContains('Exclude Extensions')
            ->assertBodyContains('Generate');
    }
    
    public function testZipReturnsNullWhenNoFilesFound()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip();
        });

        $http = $this->fakeHttp();

        // User requests ZIP but entity has no file fields or no matching files
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
                // No file fields seeded, so nothing to ZIP
            ],
        );

        // Seed entity WITHOUT any file fields
        $this->getSeedFactory([
            'title' => 'Entity without files',
        ])->create();

        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodyContains('No files found to ZIP.')
            ->assertCrudIndexEntityCount(1);
    }

    public function testZipsUsingFileSourceField()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
            ],
        );

        $this->getSeedFactory([
            'file_source' => 'source.txt',
        ])->create();

        // Default storage for file-source fields
        $fileStorage->storage('uploads-public')->write('source.txt', 'source-content');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: ['source.txt']
        );
    }
    
    public function testZipsUsingFileField()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
            ],
        );
        
        $this->getSeedFactory(['file' => ['src' => 'doc.txt', 'storage' => 'uploads-private']])->times(1)->create();
        $fileStorage->storage(name: 'uploads-private')->write(path: 'doc.txt', content: 'content');
        
        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');
        
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: ['doc.txt']
        );
    }
    
    public function testZipsUsingFileFieldTranslated()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
            ],
        );

        $this->getSeedFactory([
            'file' => [
                'src' => ['en' => 'doc-en.txt'],
                'storage' => 'uploads-private',
            ]
        ])->create();

        $fileStorage->storage('uploads-private')->write('doc-en.txt', 'content-en');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: ['doc-en.txt']
        );
    }
    
    public function testZipsUsingFilesField()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
            ],
        );

        $this->getSeedFactory([
            'files' => [
                [
                    'src' => 'a.txt',
                    'storage' => 'uploads-public',
                ],
                [
                    'src' => 'b.txt',
                    'storage' => 'uploads-public',
                ],
            ]
        ])->create();

        $fileStorage->storage('uploads-public')->write('a.txt', 'aaa');
        $fileStorage->storage('uploads-public')->write('b.txt', 'bbb');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: ['a.txt', 'b.txt']
        );

        $this->assertZipFileCount(
            zipBinary: $response->response()->getBody(),
            expectedCount: 2
        );
    }
    
    public function testZipsUsingFilesFieldTranslated()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
            ],
        );

        $this->getSeedFactory([
            'files' => [
                [
                    'src' => ['en' => 'a-en.txt'],
                    'storage' => 'uploads-public',
                ],
                [
                    'src' => ['en' => 'b-en.txt'],
                    'storage' => 'uploads-public',
                ],
            ]
        ])->create();

        $fileStorage->storage('uploads-public')->write('a-en.txt', 'aaa-en');
        $fileStorage->storage('uploads-public')->write('b-en.txt', 'bbb-en');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: ['a-en.txt', 'b-en.txt']
        );

        $this->assertZipFileCount(
            zipBinary: $response->response()->getBody(),
            expectedCount: 2
        );
    }
    
    public function testZipOnlyStoragesPublic()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip()->onlyStorages('uploads-public');
        });

        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
            ],
        );

        // Field definitions (conceptually):
        // file_source -> storage: uploads-public
        // file        -> storage: uploads-private
        
        $this->getSeedFactory([
            'file_source' => 'public.txt',
            'file' => [
                'src' => 'private.txt',
                'storage' => 'uploads-private',
            ],
        ])->create();

        $fileStorage->storage('uploads-public')->write('public.txt', 'pub');
        $fileStorage->storage('uploads-private')->write('private.txt', 'priv');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: ['public.txt']
        );

        $this->assertZipNotContains(
            zipBinary: $response->response()->getBody(),
            unexpectedFiles: ['private.txt']
        );
    }
    
    public function testZipExceptStoragesPrivate()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip()->exceptStorages('uploads-private');
        });

        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
            ],
        );

        // Field definitions (conceptually):
        // file_source -> storage: uploads-public
        // file        -> storage: uploads-private

        $this->getSeedFactory([
            'file_source' => 'public.txt',
            'file' => [
                'src' => 'private.txt',
                'storage' => 'uploads-private',
            ],
        ])->create();

        $fileStorage->storage('uploads-public')->write('public.txt', 'pub');
        $fileStorage->storage('uploads-private')->write('private.txt', 'priv');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // Should include public
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: ['public.txt']
        );

        // Should exclude private
        $this->assertZipNotContains(
            zipBinary: $response->response()->getBody(),
            unexpectedFiles: ['private.txt']
        );
    }
    
    public function testZipOnlyPublicStorages()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip()->onlyPublicStorages();
        });

        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
            ],
        );

        // Field definitions (conceptually):
        // file_source -> storage: uploads-public (public)
        // file        -> storage: uploads-private (private)

        $this->getSeedFactory([
            'file_source' => 'public.txt',
            'file' => [
                'src' => 'private.txt',
                'storage' => 'uploads-private',
            ],
        ])->create();

        $fileStorage->storage('uploads-public')->write('public.txt', 'pub');
        $fileStorage->storage('uploads-private')->write('private.txt', 'priv');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // Should include public
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: ['public.txt']
        );

        // Should exclude private
        $this->assertZipNotContains(
            zipBinary: $response->response()->getBody(),
            unexpectedFiles: ['private.txt']
        );
    }
    
    public function testZipOnlyExtensionsJpgPng()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip()->onlyExtensions('jpg', 'png');
        });

        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
            ],
        );

        // Field definitions (conceptually):
        // file_source -> storage: uploads-public
        // file        -> storage: uploads-private

        $this->getSeedFactory([
            'file_source' => 'image.jpg', // allowed
            'file' => [
                'src' => 'document.pdf',  // excluded
                'storage' => 'uploads-private',
            ],
        ])->create();

        $fileStorage->storage('uploads-public')->write('image.jpg', 'jpg-content');
        $fileStorage->storage('uploads-private')->write('document.pdf', 'pdf-content');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // Should include jpg
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: ['image.jpg']
        );

        // Should exclude pdf
        $this->assertZipNotContains(
            zipBinary: $response->response()->getBody(),
            unexpectedFiles: ['document.pdf']
        );
    }
    
    public function testZipExceptExtensionsPdfTxt()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip()->exceptExtensions('pdf', 'txt');
        });

        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
            ],
        );

        // Field definitions (conceptually):
        // file_source -> storage: uploads-public
        // file        -> storage: uploads-private

        $this->getSeedFactory([
            'file_source' => 'image.jpg', // allowed
            'file' => [
                'src' => 'document.pdf',  // excluded
                'storage' => 'uploads-private',
            ],
        ])->create();

        $fileStorage->storage('uploads-public')->write('image.jpg', 'jpg-content');
        $fileStorage->storage('uploads-private')->write('document.pdf', 'pdf-content');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // Should include jpg
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: ['image.jpg']
        );

        // Should exclude pdf
        $this->assertZipNotContains(
            zipBinary: $response->response()->getBody(),
            unexpectedFiles: ['document.pdf']
        );
    }
    
    public function testZipOnlyFieldsFileSourceAndFiles()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip()->onlyFields('file_source', 'files');
        });

        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
            ],
        );

        // Field definitions (conceptually):
        // file_source -> storage: uploads-public
        // file        -> storage: uploads-private
        // files       -> storage: uploads-public

        $this->getSeedFactory([
            'file_source' => 'public.txt', // should be included
            'file' => [                    // should be excluded
                'src' => 'private.txt',
                'storage' => 'uploads-private',
            ],
            'files' => [                   // should be included
                [
                    'src' => 'multi1.jpg',
                    'storage' => 'uploads-public',
                ],
                [
                    'src' => 'multi2.png',
                    'storage' => 'uploads-public',
                ],
            ],
        ])->create();

        $fileStorage->storage('uploads-public')->write('public.txt', 'pub');
        $fileStorage->storage('uploads-private')->write('private.txt', 'priv');
        $fileStorage->storage('uploads-public')->write('multi1.jpg', 'm1');
        $fileStorage->storage('uploads-public')->write('multi2.png', 'm2');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // Included
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: ['public.txt', 'multi1.jpg', 'multi2.png']
        );

        // Excluded
        $this->assertZipNotContains(
            zipBinary: $response->response()->getBody(),
            unexpectedFiles: ['private.txt']
        );
    }
    
    public function testZipPreservesFolderStructure()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip()->preserveFolderStructure();
        });

        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
            ],
        );

        // Field definitions (conceptually):
        // file_source -> storage: uploads-public
        // file        -> storage: uploads-private

        $this->getSeedFactory([
            'file_source' => 'images/photo.jpg', // nested path
            'file' => [
                'src' => 'docs/manual.pdf',      // nested path
                'storage' => 'uploads-private',
            ],
        ])->create();

        $fileStorage->storage('uploads-public')->write('images/photo.jpg', 'jpg-content');
        $fileStorage->storage('uploads-private')->write('docs/manual.pdf', 'pdf-content');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // Should preserve folder structure inside ZIP
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: [
                'images/photo.jpg',
                'docs/manual.pdf',
            ]
        );
    }
    
    public function testZipFlattensFolderStructure()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip()->flatten();
        });

        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
            ],
        );

        // Field definitions (conceptually):
        // file_source -> storage: uploads-public
        // file        -> storage: uploads-private

        $this->getSeedFactory([
            'file_source' => 'images/photo.jpg', // nested path
            'file' => [
                'src' => 'docs/manual.pdf',      // nested path
                'storage' => 'uploads-private',
            ],
        ])->create();

        $fileStorage->storage('uploads-public')->write('images/photo.jpg', 'jpg-content');
        $fileStorage->storage('uploads-private')->write('docs/manual.pdf', 'pdf-content');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // Flattened ZIP should contain only the basenames
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: [
                'photo.jpg',
                'manual.pdf',
            ]
        );

        // And should NOT contain folder paths
        $this->assertZipNotContains(
            zipBinary: $response->response()->getBody(),
            unexpectedFiles: [
                'images/photo.jpg',
                'docs/manual.pdf',
            ]
        );
    }
    
    public function testZipSelectionModeIdsFromUserInput()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip();
        });

        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        // User selects only entity ID 2
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['2'],
                'download-zip_name' => 'Foo',
                'download-zip_selection_mode' => 'ids',
            ],
        );

        // Seed multiple entities
        $this->getSeedFactory([
            'file_source' => 'images/one.jpg',
        ])->create(); // ID 1

        $this->getSeedFactory([
            'file_source' => 'images/two.jpg',
        ])->create(); // ID 2

        $this->getSeedFactory([
            'file_source' => 'images/three.jpg',
        ])->create(); // ID 3

        // Write files
        $fileStorage->storage('uploads-public')->write('images/one.jpg', 'one');
        $fileStorage->storage('uploads-public')->write('images/two.jpg', 'two');
        $fileStorage->storage('uploads-public')->write('images/three.jpg', 'three');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // ZIP should contain only ID 2's file
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: [
                'two.jpg',
            ]
        );

        // ZIP should NOT contain ID 1 or ID 3 files
        $this->assertZipNotContains(
            zipBinary: $response->response()->getBody(),
            unexpectedFiles: [
                'one.jpg',
                'three.jpg',
            ]
        );
    }

    public function testZipSelectionModeFilteredFromUserInput()
    {
        $http = $this->fakeHttp();

        // First request: apply filter
        $http->request(
            method: 'GET',
            uri: $this->generateIndexUri(),
            query: ['filter' => ['email' => 'tim@example.com']],
            body: [
                'download-zip_selection_mode' => 'filtered',
            ],
        );

        // Seed data
        $this->getSeedFactory(['email' => 'tom@example.com', 'file_source' => 'images/tom.jpg'])
            ->times(3)->create();

        $this->getSeedFactory(['email' => 'tim@example.com', 'file_source' => 'images/tim.jpg'])
            ->times(4)->create();

        // Write files
        $fileStorage = $this->fakeFileStorage();
        $fileStorage->storage('uploads-public')->write('images/tom.jpg', 'tom-content');
        $fileStorage->storage('uploads-public')->write('images/tim.jpg', 'tim-content');

        $http->response()->assertStatus(200);

        // Second request: perform ZIP download
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'download-zip_selection_mode' => 'filtered',
                'download-zip_name' => 'FilteredZip',
            ],
        );

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // ZIP should contain only tim@example.com files
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: [
                'tim.jpg',
            ]
        );

        // ZIP should NOT contain tom@example.com files
        $this->assertZipNotContains(
            zipBinary: $response->response()->getBody(),
            unexpectedFiles: [
                'tom.jpg',
            ]
        );
    }
    
    public function testZipOnlyPublicStoragesFromUserInput()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip();
        });

        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        
        // Request ZIP with only_public_storages = 1
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
                'download-zip_only_public_storages' => '1',
            ],
        );

        // Seed one public file and one private file
        $this->getSeedFactory([
            'file_source' => 'public/photo.jpg',
            'file' => [
                'src' => 'private/manual.pdf',
                'storage' => 'uploads-private',
            ],
        ])->create();

        // Write files
        $fileStorage->storage('uploads-public')->write('public/photo.jpg', 'jpg-content');
        $fileStorage->storage('uploads-private')->write('private/manual.pdf', 'pdf-content');
        
        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // ZIP should contain only the public file
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: [
                'photo.jpg',
            ]
        );

        // ZIP should NOT contain the private file
        $this->assertZipNotContains(
            zipBinary: $response->response()->getBody(),
            unexpectedFiles: [
                'manual.pdf',
            ]
        );
    }
    
    public function testZipOnlyExtensionsFromUserInput()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip();
        });

        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        // User requests only jpg and pdf files
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
                'download-zip_only_extensions' => 'jpg, pdf',
            ],
        );

        // Seed files with different extensions
        $this->getSeedFactory([
            'file_source' => 'images/photo.jpg',
            'file' => [
                'src' => 'docs/manual.pdf',
                'storage' => 'uploads-private',
            ],
            'extra_file' => [
                'src' => 'misc/readme.txt',
                'storage' => 'uploads-public',
            ],
        ])->create();

        // Write files
        $fileStorage->storage('uploads-public')->write('images/photo.jpg', 'jpg-content');
        $fileStorage->storage('uploads-private')->write('docs/manual.pdf', 'pdf-content');
        $fileStorage->storage('uploads-public')->write('misc/readme.txt', 'txt-content');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // ZIP should contain only jpg and pdf
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: [
                'photo.jpg',
                'manual.pdf',
            ]
        );

        // ZIP should NOT contain txt
        $this->assertZipNotContains(
            zipBinary: $response->response()->getBody(),
            unexpectedFiles: [
                'readme.txt',
            ]
        );
    }
    
    public function testZipExceptExtensionsFromUserInput()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip();
        });

        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        // User excludes txt and png files
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
                'download-zip_except_extensions' => 'txt, png',
            ],
        );

        // Seed files with different extensions
        $this->getSeedFactory([
            'file_source' => 'images/photo.jpg', // allowed
            'file' => [
                'src' => 'docs/manual.pdf',       // allowed
                'storage' => 'uploads-private',
            ],
            'extra_file' => [
                'src' => 'misc/readme.txt',       // excluded
                'storage' => 'uploads-public',
            ],
            'another_file' => [
                'src' => 'icons/logo.png',        // excluded
                'storage' => 'uploads-public',
            ],
        ])->create();

        // Write files
        $fileStorage->storage('uploads-public')->write('images/photo.jpg', 'jpg-content');
        $fileStorage->storage('uploads-private')->write('docs/manual.pdf', 'pdf-content');
        $fileStorage->storage('uploads-public')->write('misc/readme.txt', 'txt-content');
        $fileStorage->storage('uploads-public')->write('icons/logo.png', 'png-content');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // ZIP should contain only allowed extensions
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: [
                'photo.jpg',
                'manual.pdf',
            ]
        );

        // ZIP should NOT contain excluded extensions
        $this->assertZipNotContains(
            zipBinary: $response->response()->getBody(),
            unexpectedFiles: [
                'readme.txt',
                'logo.png',
            ]
        );
    }
    
    public function testZipOnlyFieldsFromUserInput()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip();
        });

        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        // User selects only the "file_source" field
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'Foo',
                'download-zip_only_fields' => ['file_source'],
            ],
        );

        // Seed entity with two file fields
        $this->getSeedFactory([
            'file_source' => 'images/photo.jpg', // should be included
            'file' => [
                'src' => 'docs/manual.pdf',       // should be excluded
                'storage' => 'uploads-private',
            ],
        ])->create();

        // Write files
        $fileStorage->storage('uploads-public')->write('images/photo.jpg', 'jpg-content');
        $fileStorage->storage('uploads-private')->write('docs/manual.pdf', 'pdf-content');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // ZIP should contain only the file_source file
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: [
                'photo.jpg',
            ]
        );

        // ZIP should NOT contain the "file" field file
        $this->assertZipNotContains(
            zipBinary: $response->response()->getBody(),
            unexpectedFiles: [
                'manual.pdf',
            ]
        );
    }
    
    public function testZipNameOverrideFromUserInput()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip();
        });

        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        // User overrides the ZIP name
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['1'],
                'download-zip_name' => 'MyCustomArchive',
            ],
        );

        // Seed entity with a file
        $this->getSeedFactory([
            'file_source' => 'images/photo.jpg',
        ])->create();

        // Write file
        $fileStorage->storage('uploads-public')->write('images/photo.jpg', 'jpg-content');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip')
            ->assertHasHeader('Content-Disposition', 'attachment; filename=MyCustomArchive');

        // ZIP should contain the file
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: [
                'photo.jpg',
            ]
        );
    }
}