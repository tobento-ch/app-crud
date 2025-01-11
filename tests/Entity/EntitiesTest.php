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

namespace Tobento\App\Crud\Test\Entity;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Entity\Entities;
use Tobento\App\Crud\Entity\EntitiesInterface;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Entity\EntityInterface;

class EntitiesTest extends TestCase
{
    public function testConstructorMethod()
    {
        $entities = new Entities();
        $this->assertInstanceof(EntitiesInterface::class, $entities);
        
        $entities = new Entities([new Entity()]);
        $this->assertFalse($entities->empty());
    }
    
    public function testFilterMethod()
    {
        $entities = new Entities([
            new Entity(['group' => 'foo']),
            new Entity(['group' => 'bar']),
        ]);
        
        $filtered = $entities->filter(fn(EntityInterface $e): bool => $e->get('group') === 'foo');
        
        $this->assertFalse($entities === $filtered);
        $this->assertSame(2, $entities->count());
        $this->assertSame(1, $filtered->count());
    }
    
    public function testFirstMethod()
    {
        $entities = new Entities();
        $this->assertSame(null, $entities->first());
        
        $entities = new Entities([
            new Entity(['id' => 'foo']),
            new Entity(['id' => 'bar']),
        ]);
        
        $this->assertSame('foo', $entities->first()->id());
    }
    
    public function testMapMethod()
    {
        $entities = new Entities([
            new Entity(['id' => 'foo']),
            new Entity(['id' => 'bar']),
        ]);
        
        $mapped = $entities->map(function(EntityInterface $entity): EntityInterface {
            return new Entity(['id' => 'new']);
        });
        
        $this->assertFalse($entities === $mapped);
        $this->assertSame('new', $mapped->first()->id());
    }
    
    public function testAllMethod()
    {
        $entities = new Entities();
        $this->assertSame([], $entities->all());
        
        $entity = new Entity();
        $entities = new Entities([$entity]);
        $this->assertSame([$entity], $entities->all());
    }
    
    public function testIdsMethod()
    {
        $entities = new Entities();
        $this->assertSame([], $entities->ids());
        
        $entities = new Entities([
            new Entity(['id' => 'foo']),
            new Entity(['id' => 'bar']),
        ]);
        $this->assertSame(['foo', 'bar'], $entities->ids());
    }
    
    public function testColumnMethod()
    {
        $entities = new Entities();
        $this->assertSame([], $entities->column('id'));
        
        $entities = new Entities([
            new Entity(['id' => 'foo', 'title' => ['en' => 'Foo']]),
            new Entity(['id' => 'bar', 'title' => ['en' => 'Bar']]),
        ]);
        
        $this->assertSame(['foo', 'bar'], $entities->column('id'));
        $this->assertSame(['foo' => 'Foo', 'bar' => 'Bar'], $entities->column('title.en', 'id'));
    }
    
    public function testEmptyMethod()
    {
        $entities = new Entities();
        $this->assertTrue($entities->empty());
        
        $entities = new Entities([new Entity()]);
        $this->assertFalse($entities->empty());
    }
    
    public function testCountMethod()
    {
        $entities = new Entities();
        $this->assertSame(0, $entities->count());
        
        $entities = new Entities([new Entity(), new Entity()]);
        $this->assertSame(2, $entities->count());
    }
    
    public function testIteration()
    {
        $entities = new Entities([new Entity(), new Entity()]);
        
        foreach($entities as $entity) {
            $this->assertInstanceof(EntityInterface::class, $entity);
        }
    }
}