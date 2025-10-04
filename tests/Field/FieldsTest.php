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

namespace Tobento\App\Crud\Test\Field;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field\FieldsInterface;

class FieldsTest extends TestCase
{
    public function testConstructorMethod()
    {
        $fields = new Fields();
        $this->assertSame(0, $fields->count());
        $this->assertInstanceof(FieldsInterface::class, $fields);
        
        $fields = new Fields(new Field\Text('name'));
        $this->assertSame(1, $fields->count());
    }
    
    public function testFilterMethod()
    {
        $fields = new Fields(
            new Field\Text('foo'),
            new Field\Text('bar'),
        );
        
        $fieldsNew = $fields->filter(fn(FieldInterface $f): bool => $f->name() === 'foo');
        
        $this->assertFalse($fields === $fieldsNew);
        $this->assertSame(2, $fields->count());
        $this->assertSame(1, $fieldsNew->count());
    }
    
    public function testGroupMethod()
    {
        $fields = new Fields(
            new Field\Text('foo')->group('a'),
            new Field\Text('bar')->group('b'),
        );
        
        $fieldsNew = $fields->group('a');
        
        $this->assertFalse($fields === $fieldsNew);
        $this->assertSame(2, $fields->count());
        $this->assertSame(1, $fieldsNew->count());
        $this->assertSame(0, $fields->group('c')->count());
    }
    
    public function testParentMethod()
    {
        $fields = new Fields(
            new Field\Text('foo'),
            new Field\Text('bar')->parent('foo'),
            new Field\Text('baz'),
        );
        
        $fieldsNew = $fields->parent('foo');
        
        $this->assertFalse($fields === $fieldsNew);
        $this->assertSame(3, $fields->count());
        $this->assertSame(1, $fieldsNew->count());
        $this->assertSame(2, $fields->parent(null)->count());
    }
    
    public function testWithParentFieldsMethod()
    {
        $fields = new Fields(
            new Field\Text('foo'),
            new Field\Group('bar')->fields(new Field\Text('baz')),
        );
        
        $fieldsNew = $fields->withParentFields(new Action\Index());
        
        $this->assertFalse($fields === $fieldsNew);
        $this->assertSame(2, $fields->count());
        $this->assertSame(3, $fieldsNew->count());
    }
    
    public function testWithChildFieldsMethod()
    {
        $fields = new Fields(
            new Field\Text('foo'),
            new Field\File('bar'),
        );
        
        $fieldsNew = $fields->withChildFields(new Action\Index());
        
        $this->assertFalse($fields === $fieldsNew);
        $this->assertSame(2, $fields->count());
        $this->assertSame(4, $fieldsNew->count());
    }
    
    public function testTranslatableMethod()
    {
        $fields = new Fields(
            new Field\Text('foo')->translatable(),
            new Field\Text('bar')->translatable(),
            new Field\Text('baz')->translatable(false),
        );
        
        $fieldsNew = $fields->translatable();
        
        $this->assertFalse($fields === $fieldsNew);
        $this->assertSame(3, $fields->count());
        $this->assertSame(2, $fieldsNew->count());
        $this->assertSame(1, $fields->translatable(false)->count());
    }
    
    public function testCreatableMethod()
    {
        $fields = new Fields(
            new Field\Text('foo')->creatable(),
            new Field\Text('bar')->creatable(),
            new Field\Text('baz')->creatable(false),
        );
        
        $fieldsNew = $fields->creatable();
        
        $this->assertFalse($fields === $fieldsNew);
        $this->assertSame(3, $fields->count());
        $this->assertSame(2, $fieldsNew->count());
        $this->assertSame(1, $fields->creatable(false)->count());
    }
    
    public function testEditableMethod()
    {
        $fields = new Fields(
            new Field\Text('foo')->editable(),
            new Field\Text('bar')->editable(),
            new Field\Text('baz')->editable(false),
        );
        
        $fieldsNew = $fields->editable();
        
        $this->assertFalse($fields === $fieldsNew);
        $this->assertSame(3, $fields->count());
        $this->assertSame(2, $fieldsNew->count());
        $this->assertSame(1, $fields->editable(false)->count());
    }
    
    public function testShowableMethod()
    {
        $fields = new Fields(
            new Field\Text('foo')->showable(),
            new Field\Text('bar')->showable(),
            new Field\Text('baz')->showable(false),
        );
        
        $fieldsNew = $fields->showable();
        
        $this->assertFalse($fields === $fieldsNew);
        $this->assertSame(3, $fields->count());
        $this->assertSame(2, $fieldsNew->count());
        $this->assertSame(1, $fields->showable(false)->count());
    }
    
    public function testStorableMethod()
    {
        $fields = new Fields(
            new Field\Text('foo')->storable(),
            new Field\Text('bar')->storable(),
            new Field\Text('baz')->storable(false),
        );
        
        $fieldsNew = $fields->storable();
        
        $this->assertFalse($fields === $fieldsNew);
        $this->assertSame(3, $fields->count());
        $this->assertSame(2, $fieldsNew->count());
        $this->assertSame(1, $fields->storable(false)->count());
    }
    
    public function testColumnMethod()
    {
        $fields = new Fields(
            new Field\Text('foo', 'Foo'),
            new Field\Text('bar', 'Bar'),
        );
        
        $this->assertSame(['foo', 'bar'], $fields->column('name'));
        $this->assertSame(['Foo' => 'foo', 'Bar' => 'bar'], $fields->column(key: 'name', index: 'label'));
        $this->assertSame([], $fields->column('unknown'));
    }
    
    public function testGetNamesMethod()
    {
        $fields = new Fields(
            new Field\Text('foo'),
            new Field\Text('bar'),
        );
        
        $this->assertSame(['foo', 'bar'], $fields->getNames());
    }
    
    public function testGetMethod()
    {
        $field = new Field\Text('foo');
        $fields = new Fields($field);
        
        $this->assertSame($field, $fields->get('foo'));
        $this->assertNull($fields->get('bar'));
    }
    
    public function testEmptyMethod()
    {
        $this->assertFalse((new Fields(new Field\Text('foo')))->empty());
        $this->assertTrue((new Fields())->empty());
    }
    
    public function testGetValidationRulesForActionMethod()
    {
        $fields = new Fields(
            new Field\Text('foo')->validate('string'),
            new Field\Text('bar'),
        );
        
        $this->assertSame(['foo' => 'string'], $fields->getValidationRulesForAction('create'));
    }
    
    public function testGetIteraterMethod()
    {
        $fields = new Fields(
            new Field\Text('foo'),
            new Field\Text('bar'),
        );
        
        foreach($fields as $field) {
            $this->assertInstanceof(FieldInterface::class, $field);
        }
    }
    
    public function testCountMethod()
    {
        $this->assertSame(1, (new Fields(new Field\Text('foo')))->count());
        $this->assertSame(0, (new Fields())->count());
    }
    
    public function testThatFieldsGetCloned()
    {
        $fields = new Fields(new Field\Text('foo'));
        $fieldsNew = clone $fields;
        
        $this->assertFalse($fields->get('foo') === $fieldsNew->get('foo'));
    }
}