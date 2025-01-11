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
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Boot\Crud;
use Closure;

/**
 * Test case to support creating crud controller on the fly.
 */
abstract class TestCase extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
{
    protected null|Closure $withCrudController = null;
    
    abstract protected function createCrudController(AppInterface $app): AbstractCrudController;
    
    public function bootingApp(): AppInterface
    {
        $app = $this->getApp()->booting();
        
        if (is_callable($this->withCrudController)) {
            $controller = $app->call($this->withCrudController);
        } else {
            $controller = $this->createCrudController($app);
        }
        
        $app->set('crudController', $controller);
        
        $app->get(Crud::class)->routeController($controller);
        return $app;
    }

    protected function getCrudController(): string
    {
        return 'crudController';
    }
    
    protected function getCrudControllerResourceName(): string
    {
        return 'users';
    }
    
    protected function withCrudController(null|Closure $callback): static
    {
        $this->withCrudController = $callback;
        return $this;
    }
}