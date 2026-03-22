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
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Boot\Crud;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Test\Factory;
use Tobento\Service\FileStorage\Repository\FileRepository;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Storage\StorageInterface;

/**
 * Tests the BulkDownloadZip action when used with a FileRepository.
 *
 * This suite focuses specifically on repository‑driven behavior:
 * - extension filtering
 * - storage filtering (configured programmatically)
 * - folder structure preservation / flattening
 * - ZIP creation for single and multiple files
 *
 * All UI‑driven and user‑input‑driven behaviors (modal rendering,
 * field‑based file selection, translated fields, name overrides, etc.)
 * are covered in the BulkDownloadZipTest test class.
 *
 * Together, both classes provide full coverage of the ZIP action:
 * - one from the CRUD/UI perspective
 * - one from the repository/data‑layer perspective
 */
class TestZipFileRepositoryCrudController extends AbstractCrudController
{
    public const RESOURCE_NAME = 'files';

    public function __construct(
        RepositoryInterface $repository,
        protected null|Action\BulkDownloadZip $zipAction = null,
    ) {
        $this->repository = $repository;
    }

    protected function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        yield new Field\Text('path');
        yield new Field\Text('name');
        yield new Field\Text('extension');
        yield new Field\Text('size');
        yield new Field\Text('created_at');
    }
    
    protected function configureActions(): iterable|ActionsInterface
    {
        yield new Action\Index('Files');
        yield new Action\Create();
        
        if ($this->zipAction) {
            yield $this->zipAction;
        } else {
            yield new Action\BulkDownloadZip();
        }
    }
    
    protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
    {
        yield new Filter\Input(name: 'name', field: 'name');
    }
    
    public function entityIdName(): string
    {
        // Always unique, even in recursive directories
        return 'path';
    }

    public function createEntityFromObject(object $object): EntityInterface
    {
        return new Entity(
            attributes: [
                'path' => $object->path(),
                'name' => $object->name(),
                'extension' => $object->extension(),
                'size' => $object->size(),
                'created_at' => $object->lastModified(),
            ],
            idAttributeName: $this->entityIdName(),
        );
    }
}

class BulkDownloadZipFileRepositoryTest extends \Tobento\App\Crud\Test\Feature\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    use \Tobento\App\Testing\FileStorage\RefreshFileStorages;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../../..');    
        $app->boot(Crud::class);
        return $app;
    }
    
    protected function getCrudControllerResourceName(): string
    {
        return 'files';
    }

    protected function createRepository(AppInterface $app): RepositoryInterface
    {
        $storages = $app->get(StoragesInterface::class);
        $storage = $storages->get('uploads-private');
        return new FileRepository(storage: $storage, recursive: true);
    }
    
    protected function createCrudController(AppInterface $app): AbstractCrudController
    {
        return new TestZipFileRepositoryCrudController(
            repository: $this->createRepository($app),
        );
    }
    
    protected function withZipAction(\Closure $callback): static
    {
        $this->withCrudController(function (AppInterface $app) use ($callback) {
            $action = $app->call($callback);
            return new TestZipFileRepositoryCrudController(
                repository: $this->createRepository($app),
                zipAction: $action,
            );
        });
        
        return $this;
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

    public function testZipsSingleFile()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        // Trigger the ZIP action
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['source.txt'],
                'download-zip_name' => 'Foo',
            ],
        );

        $app = $this->bootingApp();

        // Create the file in the repository storage
        $fileStorage->storage('uploads-private')->write('source.txt', 'source-content');

        // Get the response
        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // Assert ZIP contains the file
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: ['source.txt']
        );
    }

    public function testZipsOnlyAllowedExtensions()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        // Trigger ZIP action with allowed extensions
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['a.txt', 'b.jpg'],
                'download-zip_name' => 'Foo',
                'download-zip_only_extensions' => 'txt',
            ],
        );

        $app = $this->bootingApp();

        // Create files in the repository storage
        $fileStorage->storage('uploads-private')->write('a.txt', 'A');
        $fileStorage->storage('uploads-private')->write('b.jpg', 'B');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // Only the .txt file should be zipped
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: ['a.txt']
        );

        $this->assertZipNotContains(
            zipBinary: $response->response()->getBody(),
            unexpectedFiles: ['b.jpg']
        );
    }
    
    public function testZipsExcludingExtensions()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        // Trigger ZIP action with excluded extensions
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['excluded-a.txt', 'excluded-b.jpg'],
                'download-zip_name' => 'Foo',
                'download-zip_except_extensions' => 'jpg',
            ],
        );

        $app = $this->bootingApp();

        // Create files in the repository storage
        $fileStorage->storage('uploads-private')->write('excluded-a.txt', 'A');
        $fileStorage->storage('uploads-private')->write('excluded-b.jpg', 'B');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // Only the .txt file should be zipped
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: ['excluded-a.txt']
        );

        $this->assertZipNotContains(
            zipBinary: $response->response()->getBody(),
            unexpectedFiles: ['excluded-b.jpg']
        );
    }
    
    public function testZipsWithPreservedStructure()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        // Trigger ZIP action with preserve structure enabled
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['nested/folder/structure-file.txt'],
                'download-zip_name' => 'Foo',
                'download-zip_preserve_structure' => '1',
            ],
        );

        $app = $this->bootingApp();

        // Create nested file in the repository storage
        $fileStorage->storage('uploads-private')->write('nested/folder/structure-file.txt', 'CONTENT');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // ZIP should contain the full path
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: ['nested/folder/structure-file.txt']
        );
    }
    
    public function testZipsFlattenedStructure()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        // Trigger ZIP action WITHOUT preserve structure
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['nested/folder/flat-file.txt'],
                'download-zip_name' => 'Foo',
                // no preserve_structure flag → default = false
            ],
        );

        $app = $this->bootingApp();

        // Create nested file in the repository storage
        $fileStorage->storage('uploads-private')->write('nested/folder/flat-file.txt', 'CONTENT');

        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader('Content-Type', 'application/zip');

        // ZIP should contain ONLY the filename, not the folder structure
        $this->assertZipContains(
            zipBinary: $response->response()->getBody(),
            expectedFiles: ['flat-file.txt']
        );

        // And it should NOT contain the nested path
        $this->assertZipNotContains(
            zipBinary: $response->response()->getBody(),
            unexpectedFiles: ['nested/folder/flat-file.txt']
        );
    }
    
    public function testAllowedStoragesBlocksAllFiles()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip()->onlyStorages('uploads-public');
        });
        
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        // Trigger ZIP action with allowedStorages that DO NOT match the repo storage
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['allowed-storage-test.txt'],
                'download-zip_name' => 'Foo',
            ],
        );

        $app = $this->bootingApp();

        // Create file in the repository storage
        $fileStorage->storage('uploads-private')->write('allowed-storage-test.txt', 'CONTENT');

        // The action should return "no files found" → still HTTP 200
        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodyContains('No files found to ZIP.')
            ->assertCrudIndexEntityCount(1);
    }
    
    public function testExcludedStoragesBlocksAllFiles()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip()->exceptStorages('uploads-private');
        });

        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        // Trigger ZIP action
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['excluded-storage-test.txt'],
                'download-zip_name' => 'Foo',
            ],
        );

        $app = $this->bootingApp();

        // Create file in the repository storage
        $fileStorage->storage('uploads-private')->write('excluded-storage-test.txt', 'CONTENT');

        // The action should return "no files found" → still HTTP 200
        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodyContains('No files found to ZIP.')
            ->assertCrudIndexEntityCount(1);
    }
    
    public function testOnlyPublicStoragesBlocksPrivateStorage()
    {
        $this->withZipAction(function () {
            return new Action\BulkDownloadZip()->onlyPublicStorages();
        });

        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();

        // Trigger ZIP action
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'download-zip'),
            body: [
                'ids' => ['public-storage-test.txt'],
                'download-zip_name' => 'Foo',
            ],
        );

        $app = $this->bootingApp();

        // Create file in the repository storage (private)
        $fileStorage->storage('uploads-private')->write('public-storage-test.txt', 'CONTENT');

        // The action should return "no files found" → still HTTP 200
        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodyContains('No files found to ZIP.')
            ->assertCrudIndexEntityCount(1);
    }
}