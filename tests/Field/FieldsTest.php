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
        
        $fields = new Fields(Field\Text::new('name'));
        $this->assertSame(1, $fields->count());
    }
    
    public function testFilterMethod()
    {
        $fields = new Fields(
            Field\Text::new('foo'),
            Field\Text::new('bar'),
        );
        
        $fieldsNew = $fields->filter(fn(FieldInterface $f): bool => $f->name() === 'foo');
        
        $this->assertFalse($fields === $fieldsNew);
        $this->assertSame(2, $fields->count());
        $this->assertSame(1, $fieldsNew->count());
    }
    
    public function testGroupMethod()
    {
        $fields = new Fields(
            Field\Text::new('foo')->group('a'),
            Field\Text::new('bar')->group('b'),
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
            Field\Text::new('foo'),
            Field\Text::new('bar')->parent('foo'),
            Field\Text::new('baz'),
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
            Field\Text::new('foo'),
            Field\Group::new('bar')->fields(Field\Text::new('baz')),
        );
        
        $fieldsNew = $fields->withParentFields(Action\Index::new());
        
        $this->assertFalse($fields === $fieldsNew);
        $this->assertSame(2, $fields->count());
        $this->assertSame(3, $fieldsNew->count());
    }
    
    public function testTranslatableMethod()
    {
        $fields = new Fields(
            Field\Text::new('foo')->translatable(),
            Field\Text::new('bar')->translatable(),
            Field\Text::new('baz')->translatable(false),
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
            Field\Text::new('foo')->creatable(),
            Field\Text::new('bar')->creatable(),
            Field\Text::new('baz')->creatable(false),
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
            Field\Text::new('foo')->editable(),
            Field\Text::new('bar')->editable(),
            Field\Text::new('baz')->editable(false),
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
            Field\Text::new('foo')->showable(),
            Field\Text::new('bar')->showable(),
            Field\Text::new('baz')->showable(false),
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
            Field\Text::new('foo')->storable(),
            Field\Text::new('bar')->storable(),
            Field\Text::new('baz')->storable(false),
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
            Field\Text::new('foo', 'Foo'),
            Field\Text::new('bar', 'Bar'),
        );
        
        $this->assertSame(['foo', 'bar'], $fields->column('name'));
        $this->assertSame(['Foo' => 'foo', 'Bar' => 'bar'], $fields->column(key: 'name', index: 'label'));
        $this->assertSame([], $fields->column('unknown'));
    }
    
    public function testGetNamesMethod()
    {
        $fields = new Fields(
            Field\Text::new('foo'),
            Field\Text::new('bar'),
        );
        
        $this->assertSame(['foo', 'bar'], $fields->getNames());
    }
    
    public function testGetMethod()
    {
        $field = Field\Text::new('foo');
        $fields = new Fields($field);
        
        $this->assertSame($field, $fields->get('foo'));
        $this->assertNull($fields->get('bar'));
    }
    
    public function testEmptyMethod()
    {
        $this->assertFalse((new Fields(Field\Text::new('foo')))->empty());
        $this->assertTrue((new Fields())->empty());
    }
    
    public function testGetValidationRulesForActionMethod()
    {
        $fields = new Fields(
            Field\Text::new('foo')->validate('string'),
            Field\Text::new('bar'),
        );
        
        $this->assertSame(['foo' => 'string'], $fields->getValidationRulesForAction('create'));
    }
    
    public function testGetIteraterMethod()
    {
        $fields = new Fields(
            Field\Text::new('foo'),
            Field\Text::new('bar'),
        );
        
        foreach($fields as $field) {
            $this->assertInstanceof(FieldInterface::class, $field);
        }
    }
    
    public function testCountMethod()
    {
        $this->assertSame(1, (new Fields(Field\Text::new('foo')))->count());
        $this->assertSame(0, (new Fields())->count());
    }
    
    public function testThatFieldsGetCloned()
    {
        $fields = new Fields(Field\Text::new('foo'));
        $fieldsNew = clone $fields;
        
        $this->assertFalse($fields->get('foo') === $fieldsNew->get('foo'));
    }
}