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

namespace Tobento\App\Crud\Testing;

use Tobento\App\Testing\TestCase;
use Tobento\App\Crud\Testing\HttpTestResponseMacros;
use Tobento\App\Seeding\Boot\Seeding;
use Tobento\App\Seeding\SeedersInterface;
use Tobento\App\Seeding\Repository\RepositoryFactory;
use Tobento\App\Seeding\FactoryInterface;
use Tobento\Service\Repository\RepositoryInterface;

abstract class AbstractCrudTestCase extends TestCase
{
    use UriGenerationSupport;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        HttpTestResponseMacros::applyAllAsserts();
    }
    
    /**
     * Returns the crud controller.
     *
     * @return string
     */
    abstract protected function getCrudController(): string;
    
    /**
     * Returns the crud controller repository.
     *
     * @return RepositoryInterface
     */
    protected function getCrudRepository(): RepositoryInterface
    {
        $app = $this->bootingApp();
        
        return $app->get($this->getCrudController())->repository();
    }
    
    /**
     * Returns a new seed factory created from the crud controller repository columns.
     *
     * @param array $replaces
     * @return FactoryInterface
     */
    protected function getSeedFactory(array $replaces = []): FactoryInterface
    {
        $app = $this->bootingApp();
        
        if (! $app->has(SeedersInterface::class)) {
            $app->boot(Seeding::class);
            $app->booting();
        }
        
        return RepositoryFactory::new($this->getCrudRepository(), $this->getSeedDefinition(), $replaces);
    }
    
    /**
     * Returns a seed definition for the seed factory.
     *
     * @return null|\Closure
     */
    protected function getSeedDefinition(): null|\Closure
    {
        return null;
    }
}