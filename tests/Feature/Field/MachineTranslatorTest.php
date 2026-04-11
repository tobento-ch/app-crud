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
use Tobento\App\MachineTranslator\Boot\MachineTranslator;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Storage\StorageInterface;

class MachineTranslatorTest extends \Tobento\App\Crud\Test\Feature\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;

    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../../..');
        $app->boot(Crud::class);
        $app->boot(MachineTranslator::class);
        return $app;
    }

    protected function createRepository(AppInterface $app): RepositoryInterface
    {
        return Factory::createStorageRepository(
            table: 'posts',
            columns: [
                new Column\Id(),
                new Column\Translatable('title'),
                new Column\Translatable('content'),
                new Column\Translatable('body'),
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
                new Field\Text('id'),

                // Supported fields
                new Field\Text('title')->translatable()->machineTranslator(),
                new Field\Textarea('content')->translatable()->machineTranslator(),
                new Field\TextEditor('body')->translatable()->machineTranslator(),
            ],
            actions: [
                new Action\Index(),
                new Action\Create(),
                new Action\Store(),
                new Action\Edit(),
                new Action\Update(),
                new Action\Delete(),
            ],
        );
    }
    
    protected function withField(Closure $callback): static
    {
        $this->withCrudController(function (AppInterface $app) use ($callback) {
            $field = $app->call($callback);
            return Factory::createCrudController(
                repository: $this->createRepository($app),
                resourceName: $this->getCrudControllerResourceName(),
                fields: [
                    $field,
                ],
                actions: [
                    new Action\Index(),
                    new Action\Create(),
                    new Action\Store(),
                    new Action\Edit(),
                    new Action\Update(),
                    new Action\Delete(),
                ],
            );
        });
        
        return $this;
    }

    public function testEditActionRendersMachineTranslatorForTextField()
    {
        $http = $this->fakeHttp();
        $http->request('GET', $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['title' => ['en' => 'Hello']])->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists('title')
            ->assertBodyContains('data-machine-translator');
    }
    
    public function testIndexActionRendersMachineTranslatorForTextFieldOnTableEdit()
    {
        $this->withField(function () {
            return new Field\Text('title')
                ->translatable()
                ->machineTranslator()
                ->tableEditable(true);
        });
        
        $http = $this->fakeHttp();
        $http->request('GET', $this->generateIndexUri());
        
        $this->getSeedFactory(['title' => ['en' => 'Hello']])->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists('title')
            ->assertBodyContains('data-machine-translator');
    }

    public function testEditActionRendersMachineTranslatorForTextareaField()
    {
        $http = $this->fakeHttp();
        $http->request('GET', $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['content' => ['en' => 'Hello']])->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists('content')
            ->assertBodyContains('data-machine-translator');
    }
    
    public function testIndexActionRendersMachineTranslatorForTextareaFieldOnTableEdit()
    {
        $this->withField(function () {
            return new Field\Textarea('text')
                ->translatable()
                ->machineTranslator()
                ->tableEditable(true);
        });
        
        $http = $this->fakeHttp();
        $http->request('GET', $this->generateIndexUri());
        
        $this->getSeedFactory(['text' => ['en' => 'Hello']])->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists('text')
            ->assertBodyContains('data-machine-translator');
    }

    public function testEditActionRendersMachineTranslatorForTextEditorField()
    {
        $http = $this->fakeHttp();
        $http->request('GET', $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['body' => ['en' => 'Hello']])->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists('body')
            ->assertBodyContains('data-machine-translator');
    }

    public function testMachineTranslatorDefaultAttributes()
    {
        $http = $this->fakeHttp();
        $http->request('GET', $this->generateEditUri(id: 1));

        $this->getSeedFactory(['title' => ['en' => 'Hello']])->create();

        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists('title')
            ->assertBodyContains('data-machine-translator=')
            ->assertBodyContains('class="button text-xxs ml-s"')
            ->assertBodyContains('url&quot;:')
            ->assertBodyContains('from_first&quot;:&quot;[data-field]')
            ->assertBodyContains('to&quot;:&quot;title.en')
            ->assertBodyContains('empty_message&quot;:&quot;No text found to translate from.')
            ->assertBodyContains('target_not_empty_message&quot;:&quot;Target already contains text.')
            ->assertBodyNotContains('foo&quot;:&quot;bar')
            ->assertBodyNotContains('translator&quot;:')
            ->assertBodyContains('Auto-Translate');
    }
    
    public function testMachineTranslatorCustomAttributes()
    {
        $this->withField(function () {
            return new Field\Text('title')
                ->translatable()
                ->machineTranslator(
                    translator: 'deepl',
                    label: 'Translate',
                    attributes: [
                        'class' => 'link',
                        'data-machine-translator' => [
                            'foo' => 'bar',
                        ],
                    ],
                );
        });

        $http = $this->fakeHttp();
        $http->request('GET', $this->generateEditUri(id: 1));

        $this->getSeedFactory(['title' => ['en' => 'Hello']])->create();

        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists('title')
            ->assertBodyContains('data-machine-translator=')
            ->assertBodyContains('class="link"')
            ->assertBodyContains('foo&quot;:&quot;bar')
            ->assertBodyContains('translator&quot;:&quot;deepl')
            ->assertBodyContains('Translate');
    }
}