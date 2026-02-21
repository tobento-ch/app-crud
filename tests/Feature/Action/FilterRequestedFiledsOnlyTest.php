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
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Test\Factory;
use Tobento\App\Testing\Http\AssertableJson;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Storage\StorageInterface;

class FilterRequestedFiledsOnlyTest extends \Tobento\App\Crud\Test\Feature\TestCase
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
                new Column\Text('name'),
                new Column\Json('group'),
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
                new Field\PrimaryId('id'),
                new Field\Text('name')->validate('required'),
                new Field\Group(name: 'group')
                    ->fields(
                        new Field\Text('g-title')->validate('required'),
                    ),
                new Field\Items('items')
                    ->group('Items')
                    ->fields(
                        new Field\Text('price_net', 'Price Net')->validate('required'),
                    ),
            ],
            actions: [
                new Action\Create(),
                new Action\Store(),
                new Action\Update(),
                new Action\Edit(),
                new Action\Delete(),
                new Action\Index(),
            ],
        );
    }
    
    public function testStoreActionLiveUsingItemsFailsValidation()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(
            method: 'POST',
            uri: $this->generateStoreUri(),
            headers: ['X-Requested-With' => 'XMLHttpRequest', 'Content-Type' => 'application/json', 'X-Crud-Live' => '1'],
            body: ['items' => [[]], '_changed' => 'items'],
        );
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'items', errorText: 'The items.1.price_net is required.');

        $this->assertSame(0, $this->getCrudRepository()->count());
    }
    
    public function testStoreActionLiveGroupNotIncludedDoesNotFailValidation()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());

        // Live request that ONLY sends "name"
        $http->request(
            method: 'POST',
            uri: $this->generateStoreUri(),
            headers: [
                'X-Requested-With' => 'XMLHttpRequest',
                'Content-Type' => 'application/json',
                'X-Crud-Live' => '1',
            ],
            body: [
                'name' => 'John Doe',
            ],
        );

        $response = $http->followRedirects();

        // Should NOT fail validation for group.g-title
        $response->assertStatus(200)
                 ->assertContentType('application/json')
                 ->assertJson(fn (AssertableJson $json) =>
                     $json->has('status')
                          ->has('html')
                 );

        // No entity should be stored during live validation
        $this->assertSame(0, $this->getCrudRepository()->count());
    }
    
    public function testUpdateActionAjaxUsingItemsFailsValidation()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateEditUri(id: 1));
        $http->request(
            method: 'PUT',
            uri: $this->generateUpdateUri(id: 1),
            headers: ['X-Requested-With' => 'XMLHttpRequest', 'Content-Type' => 'application/json'],
            body: ['items' => [[]]],
        );
        
        $this->getSeedFactory(['name' => 'foo'])->times(1)->create();
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'items', errorText: 'The items.1.price_net is required.');
    }
}