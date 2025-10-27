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

class TextTest extends AbstractField
{
    public function testDefaultInterfaceMethods()
    {
        $field = new Field\Text(name: 'name');
        $this->assertInstanceof(Field\Text::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(new Field\Text(name: 'name'));
        $this->renderTests(new Field\Text(name: 'name'));
        $this->nameTests(Field\Text::class);
        $this->labelTests(Field\Text::class);
        $this->groupTests(Field\Text::class);
        $this->translatableTests(new Field\Text(name: 'name'));
        $this->localeTests(new Field\Text(name: 'name'));
        $this->storableTests(new Field\Text(name: 'name'));
        $this->indexableTests(new Field\Text(name: 'name'));
        $this->creatableTests(new Field\Text(name: 'name'));
        $this->editableTests(new Field\Text(name: 'name'));
        $this->readonlyTests(new Field\Text(name: 'name'));
        $this->disabledTests(new Field\Text(name: 'name'));
        $this->entityTests(new Field\Text(name: 'name'));
        $this->validateTests(Field\Text::class);
        $this->requiredTextTests(Field\Text::class);
        $this->optionalTextTests(Field\Text::class);
        $this->infoTextTests(Field\Text::class);
    }
    
    public function testActionProcesses()
    {
        $this->processIndexTests(Field\Text::class);
        $this->processStoreTests(Field\Text::class);
        $this->processUpdateTests(Field\Text::class);
        $this->processShowTests(Field\Text::class);
    }
    
    public function testProcessCreateEdit()
    {
        $field = new Field\Text(name: 'name')->setEntity(new Entity(['name' => 'Foo']));
        $field->processCreateEdit(action: new Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<input name="name" id="name" type="text" value="Foo">', $field->render());
        
        $field = new Field\Text(name: 'name')->setEntity(new Entity(['name' => ['en' => 'Foo']]))->translatable();
        $field->processCreateEdit(action: new Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<input name="name[en]" id="name_en" type="text" value="Foo">', $field->render());
    }
    
    public function testCustomTypeIsRendered()
    {
        $field = new Field\Text(name: 'name')->setEntity(new Entity(['name' => 'Foo']))->type('email');
        $field->processCreateEdit(action: new Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<input name="name" id="name" type="email" value="Foo">', $field->render());
    }
    
    public function testCustomAttributesAreRendered()
    {
        $field = new Field\Text(name: 'name')
            ->setEntity(new Entity(['name' => 'Foo']))
            ->attributes(['data-foo' => ['foo'], 'required']);
        
        $field->processCreateEdit(action: new Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString(
            '<input data-foo=\'[&quot;foo&quot;]\' required name="name" id="name" type="text" value="Foo">',
            $field->render()
        );
    }
    
    public function testHiddenTypeDoesNotRenderFieldHtml()
    {
        $field = new Field\Text(name: 'name')->setEntity(new Entity(['name' => 'Foo']))->type('hidden');
        $field->processCreateEdit(action: new Action\Edit(), field: $field, view: Factory::createView());
        $this->assertSame('<input name="name" type="hidden" value="Foo">', $field->render());
    }
    
    public function testProcessCreateEditWithDefaultValue()
    {
        $field = new Field\Text(name: 'name')->defaultValue('Bar');
        $field->processCreateEdit(action: new Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<input name="name" id="name" type="text" value="Bar">', $field->render());
        
        $field = new Field\Text(name: 'name')->defaultValue('Bar')->setEntity(new Entity(['name' => 'Foo']));
        $field->processCreateEdit(action: new Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<input name="name" id="name" type="text" value="Foo">', $field->render());
        
        $field = new Field\Text(name: 'name')->defaultValue('Bar')->translatable();
        $field->processCreateEdit(action: new Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<input name="name[en]" id="name_en" type="text" value="Bar">', $field->render());
        
        $field = new Field\Text(name: 'name')->defaultValue(['en' => 'EN'])->translatable();
        $field->processCreateEdit(action: new Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<input name="name[en]" id="name_en" type="text" value="EN">', $field->render());
    }
    
    public function testProcessCreateEditWithValue()
    {
        $field = new Field\Text(name: 'name')->value('Bar');
        $field->processCreateEdit(action: new Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<input name="name" id="name" type="text" value="Bar">', $field->render());
        
        $field = new Field\Text(name: 'name')->value('Bar')->setEntity(new Entity(['name' => 'Foo']));
        $field->processCreateEdit(action: new Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<input name="name" id="name" type="text" value="Bar">', $field->render());
        
        $field = new Field\Text(name: 'name')->value('Bar')->translatable();
        $field->processCreateEdit(action: new Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<input name="name[en]" id="name_en" type="text" value="Bar">', $field->render());
        
        $field = new Field\Text(name: 'name')->value(['en' => 'EN'])->translatable();
        $field->processCreateEdit(action: new Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<input name="name[en]" id="name_en" type="text" value="EN">', $field->render());
    }
    
    public function testFormatValueShowAction()
    {
        $field = new Field\Text(name: 'name')
            ->setEntity(new Entity(['name' => 'Foo']))
            ->formatValue(formatter: new Field\Formatter\CssClass('text-700'), action: 'show');
        
        $field->processShowText(field: $field, view: Factory::createView());
        $this->assertStringContainsString('<span class="text-700">Foo</span>', $field->render());
        
        $field = new Field\Text(name: 'name')
            ->setEntity(new Entity(['name' => ['en' => 'Foo']]))
            ->translatable()
            ->formatValue(formatter: new Field\Formatter\CssClass('text-700'), action: 'show');
        
        $field->processShowText(field: $field, view: Factory::createView());
        $this->assertStringContainsString('<span class="text-700">Foo</span>', $field->render());
    }
    
    public function testFormatValueIndexAction()
    {
        $field = new Field\Text(name: 'name')
            ->setEntity(new Entity(['name' => 'Foo']))
            ->formatValue(formatter: new Field\Formatter\CssClass('text-700'), action: 'index');
        
        $field->processIndexAction(action: new Action\Index(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<span class="text-700">Foo</span>', $field->render());
        
        $field = new Field\Text(name: 'name')
            ->setEntity(new Entity(['name' => ['en' => 'Foo']]))
            ->translatable()
            ->formatValue(formatter: new Field\Formatter\CssClass('text-700'), action: 'index');
        
        $field->processIndexAction(action: new Action\Index(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<span class="text-700">Foo</span>', $field->render());
    }
    
    public function testLiveFeature()
    {
        $field = new Field\Text(name: 'name')->live();
        $field->processCreateEdit(action: new Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<input data-live=\'{&quot;fields&quot;:[],&quot;selectors&quot;:[],&quot;blur&quot;:false,&quot;debounce&quot;:0}\' name="name" id="name" type="text" value>', $field->render());
        $this->assertInstanceof(\Tobento\App\Crud\Field\LiveAwareInterface::class, $field);
    }
}