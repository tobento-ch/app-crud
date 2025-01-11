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

class UpdateTest extends \Tobento\App\Crud\Test\Feature\TestCase
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
                Column\Id::new(),
                Column\Text::new('email'),
                Column\Text::new('firstname'),
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
                Field\PrimaryId::new('id'),
                Field\Text::new('email')->validate('string|email'),
            ],
            actions: [
                Action\Index::new('Users'),
                Action\Edit::new(),
                Action\Update::new(),
            ],
        );
    }
    
    public function testUpdateEntityWithValidationErrors()
    {
        $this->withCrudController(function (AppInterface $app) {
            return Factory::createCrudController(
                repository: $this->createRepository($app),
                resourceName: $this->getCrudControllerResourceName(),
                fields: [
                    Field\Text::new('id'),
                    Field\Text::new('email')->validate('string|email'),
                ],
                actions: [
                    Action\Edit::new(),
                    Action\Update::new(),
                ],
            );
        });
        
        $http = $this->fakeHttp();
        $http->previousUri($this->generateEditUri(id: 1));
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'email' => [555],
        ]);
        
        //$app = $this->bootingApp();
        $this->getSeedFactory(['email' => 'foo@example.com'])->times(1)->create();

        /*$http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateEditUri(id: 1));*/

        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'email', errorText: 'The email must be a string.');
    }
}