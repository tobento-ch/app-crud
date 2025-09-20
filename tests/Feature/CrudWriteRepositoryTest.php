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

namespace Tobento\App\Crud\Test\Feature;

use Tobento\App\AppInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\Boot\Crud;
use Tobento\App\Crud\CrudWriteRepository;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\Service\Repository\RepositoryCreateException;
use Tobento\Service\Repository\RepositoryDeleteException;
use Tobento\Service\Repository\RepositoryUpdateException;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Seeder\SeedInterface;
use Tobento\Service\Seeder\Lorem;
use Tobento\Service\Storage\StorageInterface;

class CrudWriteRepositoryTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    use \Tobento\App\Testing\Http\RefreshSession;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(Crud::class);
        $app->boot(Testing\App\ArticleCrudBoot::class);
        
        $app->set(Testing\App\ArticleRepository::class, function(StorageInterface $storage) use ($app) {
            $pdo = $app->get(\Tobento\Service\Database\PdoDatabaseInterface::class)->pdo();
            return new Testing\App\ArticleRepository(
                storage: $storage->new(),
                table: 'articles',
                columns: [
                    Column\Id::new(),
                    Column\Text::new('name'),
                    Column\Translatable::new('title'),
                    Column\Text::new('desc'),
                ],
            );
        });
        
        return $app;
    }

    protected function getCrudController(): string
    {
        return Testing\App\ArticleCrudController::class;
    }
    
    protected function createRepository(AppInterface $app): CrudWriteRepository
    {
        return new CrudWriteRepository(
            controller: $app->make($this->getCrudController()),
            actionProcessor: $app->get(ActionProcessorInterface::class),
        );
    }

    public function testControllerMethod()
    {
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);
        
        $this->assertInstanceof($this->getCrudController(), $repo->controller());
    }
    
    public function testActionProcessorMethod()
    {
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);
        
        $this->assertInstanceof(ActionProcessorInterface::class, $repo->actionProcessor());
    }
    
    public function testOnlyFieldsMethod()
    {
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);
        $repoNew = $repo->onlyFields('id', 'title', 'unknown');
        
        $this->assertFalse($repo === $repoNew);
        $this->assertSame(4, $repo->getConfiguredFields(Action\Create::new())->count());
        $this->assertSame(2, $repoNew->getConfiguredFields(Action\Create::new())->count());
    }
    
    public function testExceptFieldsMethod()
    {
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);
        $repoNew = $repo->exceptFields('title', 'unknown');
        
        $this->assertFalse($repo === $repoNew);
        $this->assertSame(4, $repo->getConfiguredFields(Action\Create::new())->count());
        $this->assertSame(3, $repoNew->getConfiguredFields(Action\Create::new())->count());
    }
    
    public function testWithFieldsMethod()
    {
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);
        $repoNew = $repo->withFields(new Fields());
        
        $this->assertFalse($repo === $repoNew);
        $this->assertSame(4, $repo->getConfiguredFields(Action\Create::new())->count());
        $this->assertSame(0, $repoNew->getConfiguredFields(Action\Create::new())->count());
    }
    
    public function testWithFieldsMethodUsingClosure()
    {
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);
        $repoNew = $repo->withFields(function(FieldsInterface $fields, ActionInterface $action): FieldsInterface {
            return $fields->filter(fn (FieldInterface $f) => in_array($f->name(), ['id', 'title']));
        });
        
        $this->assertFalse($repo === $repoNew);
        $this->assertSame(4, $repo->getConfiguredFields(Action\Create::new())->count());
        $this->assertSame(2, $repoNew->getConfiguredFields(Action\Create::new())->count());
    }
    
    public function testOnlyActionsMethod()
    {
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);
        $repoNew = $repo->onlyActions('store', 'update');
        
        $this->assertFalse($repo === $repoNew);
        $this->assertSame(3, $repo->getConfiguredActions()->count());
        $this->assertSame(2, $repoNew->getConfiguredActions()->count());
    }
    
    public function testCreateMethod()
    {
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);

        $entity = $repo->create(['title' => ['en' => 'EN']]);
        
        $this->assertSame(['en' => 'EN'], $entity->get('title'));
    }
    
    public function testCreateMethodFailsIfValidationError()
    {
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);
        $message = null;
        
        try {
            $entity = $repo->create(['name' => 'foo']);
        } catch (RepositoryCreateException $e) {
            $message = $e->getPrevious()->validation()->errors()->first()->message();
        }
        
        $this->assertSame('The title.en is required.', $message);
    }
    
    public function testCreateMethodFailsIfActionNotFound()
    {
        $this->expectException(RepositoryCreateException::class);
        $this->expectExceptionMessage('Action store not found.');
        
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);
        $repo = $repo->onlyActions('update');
        
        $entity = $repo->create(['title' => ['en' => 'EN'],]);
    }
    
    public function testUpdateByIdMethod()
    {
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);
        
        $this->getSeedFactory()->times(1)->create();
        
        $entity = $repo->updateById(1, ['title' => ['en' => 'EN']]);
        
        $this->assertSame(['en' => 'EN'], $entity->get('title'));
    }
    
    public function testUpdateByIdMethodFailsIfValidationError()
    {
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);
        $message = null;
        
        $this->getSeedFactory()->times(1)->create();
        
        try {
            $entity = $repo->updateById(1, ['name' => 'foo']);
        } catch (RepositoryUpdateException $e) {
            $message = $e->getPrevious()->validation()->errors()->first()->message();
        }
        
        $this->assertSame('The title.en is required.', $message);
    }

    public function testUpdateByIdMethodFailsIfActionNotFound()
    {
        $this->expectException(RepositoryUpdateException::class);
        $this->expectExceptionMessage('Action update not found.');
        
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);
        $repo = $repo->onlyActions('store');
        
        $entity = $repo->updateById(1, ['title' => ['en' => 'EN']]);
    }
    
    public function testUpdateByIdMethodFailsIfEntityNotFound()
    {
        $this->expectException(RepositoryUpdateException::class);
        $this->expectExceptionMessage('Entity with the id 1 not found');
        
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);
        
        $entity = $repo->updateById(1, ['title' => ['en' => 'EN']]);
    }
    
    public function testUpdateMethodFailsAsUnsupported()
    {
        $this->expectException(RepositoryUpdateException::class);
        $this->expectExceptionMessage('Unsupported');
        
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);
        
        $this->getSeedFactory()->times(1)->create();
        
        $entity = $repo->update(where: [], attributes: ['title' => ['en' => 'EN']]);
    }
    
    public function testDeleteByIdMethod()
    {
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);
        
        $this->getSeedFactory()->times(1)->create();
        
        $this->assertSame(1, $repo->controller()->repository()->count());
        
        $entity = $repo->deleteById(1);
        
        $this->assertSame(0, $repo->controller()->repository()->count());
    }
    
    public function testDeleteByIdMethodFailsIfActionNotFound()
    {
        $this->expectException(RepositoryDeleteException::class);
        $this->expectExceptionMessage('Action delete not found.');
        
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);
        $repo = $repo->onlyActions('store');
        
        $this->getSeedFactory()->times(1)->create();
        
        $entity = $repo->deleteById(1);
    }
    
    public function testDeleteMethodFailsAsUnsupported()
    {
        $this->expectException(RepositoryDeleteException::class);
        $this->expectExceptionMessage('Unsupported');
        
        $app = $this->bootingApp();
        $repo = $this->createRepository($app);
        
        $this->getSeedFactory()->times(1)->create();
        
        $repo->delete(where: []);
    }
}