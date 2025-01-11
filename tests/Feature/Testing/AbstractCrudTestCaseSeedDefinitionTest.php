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

namespace Tobento\App\Crud\Test\Feature\Testing;

use Tobento\App\AppInterface;
use Tobento\App\Crud\Testing\AbstractCrudTestCase;
use Tobento\App\Crud\Boot\Crud;
use Tobento\Service\Seeder\SeedInterface;

class AbstractCrudTestCaseSeedDefinitionTest extends AbstractCrudTestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../../..');
        $app->boot(Crud::class);
        $app->boot(\Tobento\App\User\Boot\User::class);
        return $app;
    }
    
    protected function getCrudController(): string
    {
        return App\UserCrudController::class;
    }
    
    protected function getSeedDefinition(): null|\Closure
    {
        return function (SeedInterface $seed): array {
            return [
                'email' => $seed->email(),
                'username' => 'tom',
            ];
        };
    }
    
    public function testSeedDefinitionIsUsed()
    {
        $app = $this->bootingApp();
        $controller = $this->getCrudController();
        $app->get(Crud::class)->routeController($controller);
        
        $user = $this->getSeedFactory()->makeOne();
        
        $this->assertSame('tom', $user->username());
    }
}