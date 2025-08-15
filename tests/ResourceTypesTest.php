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

namespace Tobento\App\Crud\Test;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Exception\ResourceTypeNotFoundException;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\ResourceTypeInterface;
use Tobento\App\Crud\ResourceTypes;
use Tobento\App\Crud\ResourceTypesInterface;

class ResourceTypesTest extends TestCase
{
    public function testConstructorMethod()
    {
        $types = new ResourceTypes();
        $this->assertInstanceof(ResourceTypesInterface::class, $types);
        $this->assertSame([], $types->all());
        
        $type = new FooResourceType();
        $types = new ResourceTypes($type);
        $this->assertSame(['foo' => $type], $types->all());
    }
    
    public function testAddMethod()
    {
        $type = new FooResourceType();
        $types = new ResourceTypes();
        $types->add($type);
        $this->assertSame(['foo' => $type], $types->all());
    }
    
    public function testHasMethod()
    {
        $types = new ResourceTypes(new FooResourceType());
        
        $this->assertTrue($types->has('foo'));
        $this->assertFalse($types->has('bar'));
    }
    
    public function testGetMethod()
    {
        $type = new FooResourceType();
        $types = new ResourceTypes($type);

        $this->assertTrue($type === $types->get('foo'));
    }
    
    public function testGetMethodThrowsResourceTypeNotFoundExceptionIfNotFound()
    {
        $this->expectException(ResourceTypeNotFoundException::class);
        
        $types = new ResourceTypes();
        $types->get('foo');
    }
    
    public function testAllMethod()
    {
        $types = new ResourceTypes();
        $this->assertSame([], $types->all());
        
        $type = new FooResourceType();
        $types = new ResourceTypes($type);
        $this->assertSame(['foo' => $type], $types->all());
    }
    
    public function testNamesMethod()
    {
        $types = new ResourceTypes(
            new FooResourceType(),
            new BarResourceType(),
        );
        
        $this->assertSame(['foo', 'bar'], $types->names());
        $this->assertSame([], (new ResourceTypes())->names());
    }
    
    public function testTitlesMethod()
    {
        $types = new ResourceTypes(
            new FooResourceType(),
            new BarResourceType(),
        );
        
        $this->assertSame(['foo' => 'Foo', 'bar' => 'Bar'], $types->titles());
        $this->assertSame([], (new ResourceTypes())->titles());
    }
    
    public function testIteration()
    {
        $types = new ResourceTypes(
            new FooResourceType(),
            new BarResourceType(),
        );
        
        foreach($types as $type) {
            $this->assertInstanceof(ResourceTypeInterface::class, $type);
        }
    }
}

class FooResourceType implements ResourceTypeInterface
{
    public function name(): string
    {
        return 'foo';
    }
    
    public function title(): string
    {
        return 'Foo';
    }
    
    public function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        yield Field\PrimaryId::new('id');
    }

    public function configureActions(ActionsInterface $actions): void
    {
        //
    }
}

class BarResourceType implements ResourceTypeInterface
{
    public function name(): string
    {
        return 'bar';
    }
    
    public function title(): string
    {
        return 'Bar';
    }
    
    public function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        yield Field\PrimaryId::new('id');
    }

    public function configureActions(ActionsInterface $actions): void
    {
        //
    }
}