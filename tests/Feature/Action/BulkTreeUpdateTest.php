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
use Tobento\App\Crud\Test\Factory;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Storage\StorageInterface;

class BulkTreeUpdateTest extends \Tobento\App\Crud\Test\Feature\TestCase
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
            table: 'categories',
            columns: [
                new Column\Id(),
                new Column\Text('parent_id'),
                new Column\Text('sortorder'),
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
                new Field\Text('parent_id'),
                new Field\Text('sortorder'),
            ],
            actions: [
                new Action\Index('Categories')->view('crud/index-tree'),
                new Action\BulkTreeUpdate(),
                new Action\Create(),
                new Action\Update(),
            ],
        );
    }
    
    public function testBulkIsRendered()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(3)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('data-tree-update-url')
            ->assertBodyContains('data-tree-success-message')
            ->assertBodyContains('data-tree-error-message');
    }
    
    public function testUpdatesEntities()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'bulk-tree-update'),
            body: ['items' => [
                ['id' => 1, 'parent_id' => 0, 'sortorder' => 1],
                ['id' => 2, 'parent_id' => 1, 'sortorder' => 5],
                ['id' => 3, 'parent_id' => 1, 'sortorder' => 4],
            ]],
        );
        
        $this->getSeedFactory()->times(3)->create();
        
        $http->followRedirects()->assertStatus(200);
        
        $this->assertSame(['parent_id' => '1', 'sortorder' => '5', 'id' => 2], $this->getCrudRepository()->findById(2)->toArray());
        $this->assertSame(['parent_id' => '1', 'sortorder' => '4', 'id' => 3], $this->getCrudRepository()->findById(3)->toArray());
    }
}