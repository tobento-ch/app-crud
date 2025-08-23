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

use Tobento\App\Crud\Action;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Test\Factory;

class TextareaTest extends AbstractField
{
    public function testDefaultInterfaceMethods()
    {
        $field = Field\Textarea::new(name: 'name');
        $this->assertInstanceof(Field\Textarea::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(Field\Textarea::new(name: 'name'));
        $this->renderTests(Field\Textarea::new(name: 'name'));
        $this->nameTests(Field\Textarea::class);
        $this->labelTests(Field\Textarea::class);
        $this->groupTests(Field\Textarea::class);
        $this->translatableTests(Field\Textarea::new(name: 'name'));
        $this->localeTests(Field\Textarea::new(name: 'name'));
        $this->storableTests(Field\Textarea::new(name: 'name'));
        $this->indexableTests(Field\Textarea::new(name: 'name'));
        $this->creatableTests(Field\Textarea::new(name: 'name'));
        $this->editableTests(Field\Textarea::new(name: 'name'));
        $this->readonlyTests(Field\Textarea::new(name: 'name'));
        $this->disabledTests(Field\Textarea::new(name: 'name'));
        $this->entityTests(Field\Textarea::new(name: 'name'));
        $this->validateTests(Field\Textarea::class);
        $this->requiredTextTests(Field\Textarea::class);
        $this->optionalTextTests(Field\Textarea::class);
        $this->infoTextTests(Field\Textarea::class);
    }
    
    public function testActionProcesses()
    {
        $this->processIndexTests(Field\Textarea::class);
        $this->processStoreTests(Field\Textarea::class);
        $this->processUpdateTests(Field\Textarea::class);
        $this->processShowTests(Field\Textarea::class);
    }
    
    public function testProcessCreateEdit()
    {
        $field = Field\Textarea::new(name: 'name')->setEntity(new Entity(['name' => 'Foo']));
        $field->processCreateEdit(action: Action\Edit::new(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<textarea name="name" id="name">Foo</textarea>', $field->render());
        
        $field = Field\Textarea::new(name: 'name')->setEntity(new Entity(['name' => ['en' => 'Foo']]))->translatable();
        $field->processCreateEdit(action: Action\Edit::new(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<textarea name="name[en]" id="name_en">Foo</textarea>', $field->render());
    }
    
    public function testCustomAttributesAreRendered()
    {
        $field = Field\Textarea::new(name: 'name')
            ->setEntity(new Entity(['name' => 'Foo']))
            ->attributes(['data-foo' => ['foo'], 'required']);
        
        $field->processCreateEdit(action: Action\Edit::new(), field: $field, view: Factory::createView());
        $this->assertStringContainsString(
            '<textarea data-foo=\'[&quot;foo&quot;]\' required name="name" id="name">Foo</textarea>',
            $field->render()
        );
    }
    
    public function testProcessIndex()
    {
        $field = Field\Textarea::new(name: 'name')->setEntity(new Entity(['name' => 'Foo']));
        
        $field->processIndexAction(action: Action\Index::new(), field: $field, view: Factory::createView());
        
        $this->assertStringContainsString('Foo', $field->render());
    }
    
    public function testFormatValueIndexAction()
    {
        $field = Field\Textarea::new(name: 'name')
            ->setEntity(new Entity(['name' => 'Foo']))
            ->formatValue(formatter: new Field\Formatter\CssClass('text-700'), action: 'index');
        
        $field->processIndexAction(action: Action\Index::new(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<span class="text-700">Foo</span>', $field->render());
    }
    
    public function testFormatValueShowAction()
    {
        $field = Field\Textarea::new(name: 'name')
            ->setEntity(new Entity(['name' => 'Foo']))
            ->formatValue(formatter: new Field\Formatter\CssClass('text-700'), action: 'show');
        
        $field->processShow(field: $field, view: Factory::createView());
        $this->assertStringContainsString('<span class="text-700">Foo</span>', $field->render());
    }
    
    public function testFormatValueShowActionTranslatable()
    {
        $field = Field\Textarea::new(name: 'name')
            ->translatable()
            ->setEntity(new Entity(['name' => ['en' => 'Foo']]))
            ->formatValue(formatter: new Field\Formatter\CssClass('text-700'), action: 'show');
        
        $field->processShow(field: $field, view: Factory::createView());
        $this->assertStringContainsString('<span class="text-700">Foo</span>', $field->render());
    }
}