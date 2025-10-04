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
use Tobento\App\Crud\new Field\FieldInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Test\Factory;

class TextareaTest extends AbstractField
{
    public function testDefaultInterfaceMethods()
    {
        $field = new Field\Textarea(name: 'name');
        $this->assertInstanceof(new Field\Textarea::class, $field);
        $this->assertInstanceof(new Field\FieldInterface::class, $field);
        
        $this->processTests(new Field\Textarea(name: 'name'));
        $this->renderTests(new Field\Textarea(name: 'name'));
        $this->nameTests(new Field\Textarea::class);
        $this->labelTests(new Field\Textarea::class);
        $this->groupTests(new Field\Textarea::class);
        $this->translatableTests(new Field\Textarea(name: 'name'));
        $this->localeTests(new Field\Textarea(name: 'name'));
        $this->storableTests(new Field\Textarea(name: 'name'));
        $this->indexableTests(new Field\Textarea(name: 'name'));
        $this->creatableTests(new Field\Textarea(name: 'name'));
        $this->editableTests(new Field\Textarea(name: 'name'));
        $this->readonlyTests(new Field\Textarea(name: 'name'));
        $this->disabledTests(new Field\Textarea(name: 'name'));
        $this->entityTests(new Field\Textarea(name: 'name'));
        $this->validateTests(new Field\Textarea::class);
        $this->requiredTextTests(new Field\Textarea::class);
        $this->optionalTextTests(new Field\Textarea::class);
        $this->infoTextTests(new Field\Textarea::class);
    }
    
    public function testActionProcesses()
    {
        $this->processIndexTests(new Field\Textarea::class);
        $this->processStoreTests(new Field\Textarea::class);
        $this->processUpdateTests(new Field\Textarea::class);
        $this->processShowTests(new Field\Textarea::class);
    }
    
    public function testProcessCreateEdit()
    {
        $field = new Field\Textarea(name: 'name')->setEntity(new Entity(['name' => 'Foo']));
        $field->processCreateEdit(action: Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<textarea name="name" id="name">Foo</textarea>', $field->render());
        
        $field = new Field\Textarea(name: 'name')->setEntity(new Entity(['name' => ['en' => 'Foo']]))->translatable();
        $field->processCreateEdit(action: Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<textarea name="name[en]" id="name_en">Foo</textarea>', $field->render());
    }
    
    public function testCustomAttributesAreRendered()
    {
        $field = new Field\Textarea(name: 'name')
            ->setEntity(new Entity(['name' => 'Foo']))
            ->attributes(['data-foo' => ['foo'], 'required']);
        
        $field->processCreateEdit(action: Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString(
            '<textarea data-foo=\'[&quot;foo&quot;]\' required name="name" id="name">Foo</textarea>',
            $field->render()
        );
    }
    
    public function testProcessIndex()
    {
        $field = new Field\Textarea(name: 'name')->setEntity(new Entity(['name' => 'Foo']));
        
        $field->processIndexAction(action: Action\Index(), field: $field, view: Factory::createView());
        
        $this->assertStringContainsString('Foo', $field->render());
    }
    
    public function testFormatValueIndexAction()
    {
        $field = new Field\Textarea(name: 'name')
            ->setEntity(new Entity(['name' => 'Foo']))
            ->formatValue(formatter: new new Field\Formatter\CssClass('text-700'), action: 'index');
        
        $field->processIndexAction(action: Action\Index(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<span class="text-700">Foo</span>', $field->render());
    }
    
    public function testFormatValueShowAction()
    {
        $field = new Field\Textarea(name: 'name')
            ->setEntity(new Entity(['name' => 'Foo']))
            ->formatValue(formatter: new new Field\Formatter\CssClass('text-700'), action: 'show');
        
        $field->processShow(field: $field, view: Factory::createView());
        $this->assertStringContainsString('<span class="text-700">Foo</span>', $field->render());
    }
    
    public function testFormatValueShowActionTranslatable()
    {
        $field = new Field\Textarea(name: 'name')
            ->translatable()
            ->setEntity(new Entity(['name' => ['en' => 'Foo']]))
            ->formatValue(formatter: new new Field\Formatter\CssClass('text-700'), action: 'show');
        
        $field->processShow(field: $field, view: Factory::createView());
        $this->assertStringContainsString('<span class="text-700">Foo</span>', $field->render());
    }
}