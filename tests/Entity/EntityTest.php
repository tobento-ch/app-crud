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
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Button\ButtonsInterface;
use Tobento\App\Crud\Button\Buttons;

class EntityTest extends TestCase
{
    public function testThatImplementsEntityInterface()
    {
        $entity = new Entity();
        $this->assertInstanceof(EntityInterface::class, $entity);
    }
    
    public function testIdMethod()
    {
        $this->assertSame(0, (new Entity())->id());
        $this->assertSame(1, (new Entity(['id' => 1]))->id());
        $this->assertSame('ID', (new Entity(['id' => 'ID']))->id());
        $this->assertSame(0, (new Entity(['Id' => 1]))->id());
    }
    
    public function testIdMethodWithDefinedAttributeName()
    {
        $entity = new Entity(attributes: ['uuid' => 1, 'id' => 2], idAttributeName: 'uuid');
        
        $this->assertSame(1, $entity->id());
    }
    
    public function testGetMethodReturnsValueIfExists()
    {
        $this->assertSame('Foo', (new Entity(['foo' => 'Foo']))->get('foo'));
    }
    
    public function testGetMethodReturnsNullIfNotExists()
    {
        $this->assertSame(null, (new Entity())->get('foo'));
    }
    
    public function testGetMethodReturnsDefaultIfNotExists()
    {
        $this->assertSame('default', (new Entity())->get('foo', 'default'));
    }
    
    public function testGetMethodDefaultDataType()
    {
        // string:
        $e = new Entity(['foo' => 'Foo']);
        $this->assertSame('Foo', $e->get('foo', ''));
        $this->assertSame([], $e->get('foo', []));
        $this->assertSame(2, $e->get('foo', 2));
        $this->assertSame(true, $e->get('foo', true));
        
        // int/float:
        $e = new Entity(['foo' => 2]);
        $this->assertSame(2, $e->get('foo', 3));
        $this->assertSame(2, $e->get('foo', 2.5));
        $this->assertSame([], $e->get('foo', []));
        $this->assertSame(true, $e->get('foo', true));
        
        // array:
        $e = new Entity(['foo' => ['foo']]);
        $this->assertSame(['foo'], $e->get('foo', []));
        $this->assertSame(2, $e->get('foo', 2));
        $this->assertSame(true, $e->get('foo', true));
        $this->assertSame('', $e->get('foo', ''));
        
        // bool:
        $e = new Entity(['foo' => true]);
        $this->assertSame(true, $e->get('foo', false));
        $this->assertSame([], $e->get('foo', []));
        $this->assertSame(2, $e->get('foo', 2));
        $this->assertSame('', $e->get('foo', ''));
    }
    
    public function testToArrayMethod()
    {
        $this->assertSame(['id' => 1], (new Entity(['id' => 1]))->toArray());
    }
    
    public function testFieldsMethods()
    {
        $this->assertInstanceof(FieldsInterface::class, (new Entity())->fields());
        
        $fields = new Fields();
        $this->assertTrue($fields === (new Entity())->setFields($fields)->fields());
    }
    
    public function testButtonsMethods()
    {
        $this->assertInstanceof(ButtonsInterface::class, (new Entity())->buttons());
        
        $btns = new Buttons();
        $this->assertTrue($btns === (new Entity())->setButtons($btns)->buttons());
    }
}