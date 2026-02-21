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
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Storage\StorageInterface;

class DynamicBulkEditTest extends \Tobento\App\Crud\Test\Feature\TestCase
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
                new Column\Text('email'),
                new Column\Text('firstname'),
                new Column\Text('status'),
                new Column\Json('checkboxes-field'),
                new Column\Text('radios-field'),
                new Column\Text('select-field'),
                new Column\Text('textarea-field'),
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
                new Field\Text('firstname')->validate('string|htmlclean'),
                new Field\Select('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                    ->infoText(text: 'Status info create only text', action: 'create'),
                new Field\Checkboxes('checkboxes-field')
                    ->options(['foo' => 'Foo', 'bar' => 'Bar']),
                new Field\Radios('radios-field')
                    ->options(['foo' => 'Foo', 'bar' => 'Bar']),
                new Field\Textarea('textarea-field'),
            ],
            actions: [
                new Action\Index('Users'),
                new Action\DynamicBulkEdit(
                    name: 'dynamic-bulk-edit',
                    fieldLabel: 'Column',
                    valueLabel: 'New Value',
                    itemsGroupName: 'Columns to update',
                    itemsAddText: 'Add column',
                    title: 'Edit User Columns',
                )
                    ->field('status', 'email', 'firstname'),
                new Action\Create(),
                new Action\Update()->unupdatable(
                    [3],
                    fn (EntityInterface $entity): string => sprintf('ID %s unupdatable because of...', $entity->id())
                ),
            ],
        );
    }

    public function testDynamicBulkEditIsRendered()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());

        $this->getSeedFactory()->times(1)->create();

        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('<form action="http://localhost/users/bulk/dynamic-bulk-edit" name="dynamic-bulk-edit" method="POST">')
            ->assertBodyContains('Columns to update')
            ->assertBodyContains('Add column')
            ->assertBodyContains('Column')
            ->assertBodyContains('New Value');
    }
    
    public function testDynamicBulkEditIsRenderedUsingDynamicField()
    {
        // because status field is first available, so it must render that!
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());

        $this->getSeedFactory()->times(1)->create();

        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('<select id="changes_{num}_value"');
    }
    
    public function testUpdatesEntities()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'dynamic-bulk-edit'),
            body: [
                'ids' => ['1', '2', '12'],
                'changes' => [
                    1 => ['field' => 'email', 'value' => 'new@example.com'],
                    2 => ['field' => 'status', 'value' => 'inactive'],
                    3 => ['field' => 'not-exists', 'value' => 'foo'],
                ],
            ],
        );
        
        $this->getSeedFactory(['email' => 'tom@example.com', 'status' => 'active'])->times(3)->create();
        
        $http->followRedirects()->assertStatus(200)->assertCrudIndexEntityCount(3);

        $this->assertSame('new@example.com', $this->getCrudRepository()->findById(1)->get('email'));
        $this->assertSame('inactive', $this->getCrudRepository()->findById(1)->get('status'));
        $this->assertSame('new@example.com', $this->getCrudRepository()->findById(2)->get('email'));
        $this->assertSame('inactive', $this->getCrudRepository()->findById(2)->get('status'));
        $this->assertSame('tom@example.com', $this->getCrudRepository()->findById(3)->get('email'));
        $this->assertSame('active', $this->getCrudRepository()->findById(3)->get('status'));
    }

    public function testUnupdatableEntitiesAreIgnored()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'dynamic-bulk-edit'),
            body: [
                'ids' => ['1', '3'],
                'changes' => [
                    1 => ['field' => 'email', 'value' => 'new@example.com'],
                ],
            ],
        );
        
        $this->getSeedFactory(['email' => 'tom@example.com'])->times(3)->create();

        $http->followRedirects()->assertStatus(200)
            ->assertBodyContains('ID 3 unupdatable because of...')
            ->assertCrudIndexEntityCount(3);
        
        $this->assertSame('new@example.com', $this->getCrudRepository()->findById(1)->get('email'));
        $this->assertSame('tom@example.com', $this->getCrudRepository()->findById(2)->get('email'));
        $this->assertSame('tom@example.com', $this->getCrudRepository()->findById(3)->get('email'));
    }
    
    public function testUpdatesEntitiesWithoutDataWillBeIgnored()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'dynamic-bulk-edit'),
            body: ['ids' => ['1', '3']],
        );
        
        $this->getSeedFactory(['email' => 'tom@example.com'])->times(1)->create();
        
        $http->followRedirects()->assertStatus(200)->assertCrudIndexEntityCount(1);
        
        $this->assertSame('tom@example.com', $this->getCrudRepository()->findById(1)->get('email'));
    }
    
    public function testFailsWhenValidationError()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: $this->generateBulkUri(action: 'dynamic-bulk-edit'),
            body: [
                'ids' => ['1'],
                'changes' => [
                    1 => ['field' => 'email', 'value' => 'invalid-email'],
                ],
            ],
        );
        
        $this->getSeedFactory(['email' => 'tom@example.com'])->times(1)->create();
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(1)
            ->assertBodyContains('The email must be a valid email address.');
        
        $this->assertSame('tom@example.com', $this->getCrudRepository()->findById(1)->get('email'));
    }
    
    public function testDynamicBulkEditRendersAllDynamicFieldTypes()
    {
        $fieldsToTest = [
            'textarea-field' => ['<textarea'],
            'status' => ['<select', '<option value="active">Active</option>'],
            'checkboxes-field' => ['type="checkbox"'],
            'radios-field' => ['type="radio"'],
        ];

        foreach ($fieldsToTest as $field => $expectedFragments) {
            $http = $this->fakeHttp();
            $http->request(
                method: 'GET',
                uri: $this->generateIndexUri(),
                body: [
                    'changes' => [
                        1 => ['field' => $field],
                    ],
                ],
                headers: ['Content-Type' => 'application/json', 'X-Crud-Live' => '1'],
            );
            
            $this->getSeedFactory()->times(1)->create();

            $response = $http->response()->assertStatus(200);

            foreach ($expectedFragments as $fragment) {
                $response->assertBodyContains($fragment);
            }
        }
    }
}