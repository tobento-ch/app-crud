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

use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Test\Factory;

class ValueTest extends AbstractField
{
    public function testDefaultInterfaceMethods()
    {
        $field = new Field\Value(name: 'name');
        $this->assertInstanceof(Field\Value::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(new Field\Value(name: 'name'));
        $this->renderTests(new Field\Value(name: 'name'));
        $this->nameTests(Field\Value::class);
        $this->groupTests(Field\Value::class);
        $this->translatableTests(new Field\Value(name: 'name'));
        $this->localeTests(new Field\Value(name: 'name'));
        $this->storableTests(new Field\Value(name: 'name'));
        $this->assertFalse(new Field\Value(name: 'name')->isIndexable());
        $this->creatableTests(new Field\Value(name: 'name'));
        $this->editableTests(new Field\Value(name: 'name'));
        $this->readonlyTests(new Field\Value(name: 'name'));
        $this->disabledTests(new Field\Value(name: 'name'));
        $this->entityTests(new Field\Value(name: 'name'));
        $this->validateTests(Field\Value::class);
        $this->requiredTextTests(Field\Value::class);
        $this->optionalTextTests(Field\Value::class);
        $this->infoTextTests(Field\Value::class);
    }
    
    public function testActionProcesses()
    {
        $this->processIndexTests(Field\Value::class);
        $this->processStoreTests(Field\Value::class);
        $this->processUpdateTests(Field\Value::class);
        $this->processShowTests(Field\Value::class);
    }
    
    public function testProcessBeforeSaveMethodAddsValue()
    {
        $input = new Input([]);
        $field = new Field\Value(name: 'name')->value('foo');
        $field->processBeforeSave(field: $field, input: $input);
        $this->assertSame(['name' => 'foo'], $input->all());
    }
    
    public function testProcessBeforeSaveMethodWihtoutValue()
    {
        $input = new Input([]);
        $field = new Field\Value(name: 'name');
        $field->processBeforeSave(field: $field, input: $input);
        $this->assertSame(['name' => null], $input->all());
    }
    
    public function testProcessIndexActionWithString()
    {
        $field = new Field\Value(name: 'name')->setEntity(new Entity(['name' => 'foo']));
        $field->processIndexAction(field: $field);
        $this->assertSame('foo', $field->render());
    }
    
    public function testProcessIndexActionWithArray()
    {
        $field = new Field\Value(name: 'name')->setEntity(new Entity(['name' => ['foo', 'bar']]));
        $field->processIndexAction(field: $field);
        $this->assertSame('[&quot;foo&quot;,&quot;bar&quot;]', $field->render());
    }
    
    public function testProcessShowActionWithString()
    {
        $field = new Field\Value(name: 'name')->setEntity(new Entity(['name' => 'foo']));
        $field->processShow(field: $field, view: Factory::createView());
        $this->assertStringContainsString('foo', $field->render());
    }
    
    public function testFormatValueIndexAction()
    {
        $field = new Field\Value(name: 'name')
            ->setEntity(new Entity(['name' => 'Foo']))
            ->formatValue(formatter: new Field\Formatter\CssClass('text-700'), action: 'index');
        
        $field->processIndexAction(field: $field);
        $this->assertStringContainsString('<span class="text-700">Foo</span>', $field->render());
    }
    
    public function testFormatValueShowAction()
    {
        $field = new Field\Value(name: 'name')
            ->setEntity(new Entity(['name' => 'Foo']))
            ->formatValue(formatter: new Field\Formatter\CssClass('text-700'), action: 'show');
        
        $field->processShow(field: $field, view: Factory::createView());
        $this->assertStringContainsString('<span class="text-700">Foo</span>', $field->render());
    }
    
    public function testFormatValueShowActionTranslatable()
    {
        $field = new Field\Value(name: 'name')
            ->translatable()
            ->setEntity(new Entity(['name' => ['en' => 'Foo']]))
            ->formatValue(formatter: new Field\Formatter\CssClass('text-700'), action: 'show');
        
        $field->processShow(field: $field, view: Factory::createView());
        $this->assertStringContainsString('<span class="text-700">Foo</span>', $field->render());
    }
}