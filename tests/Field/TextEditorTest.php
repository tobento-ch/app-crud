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

class TextEditorTest extends AbstractField
{
    public function testDefaultInterfaceMethods()
    {
        $field = Field\TextEditor::new(name: 'name');
        $this->assertInstanceof(Field\TextEditor::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(Field\TextEditor::new(name: 'name'));
        $this->renderTests(Field\TextEditor::new(name: 'name'));
        $this->nameTests(Field\TextEditor::class);
        $this->labelTests(Field\TextEditor::class);
        $this->groupTests(Field\TextEditor::class);
        $this->translatableTests(Field\TextEditor::new(name: 'name'));
        $this->localeTests(Field\TextEditor::new(name: 'name'));
        $this->storableTests(Field\TextEditor::new(name: 'name'));
        $this->indexableTests(Field\TextEditor::new(name: 'name'));
        $this->creatableTests(Field\TextEditor::new(name: 'name'));
        $this->editableTests(Field\TextEditor::new(name: 'name'));
        $this->readonlyTests(Field\TextEditor::new(name: 'name'));
        $this->disabledTests(Field\TextEditor::new(name: 'name'));
        $this->entityTests(Field\TextEditor::new(name: 'name'));
        $this->validateTests(Field\TextEditor::class);
        $this->requiredTextTests(Field\TextEditor::class);
        $this->optionalTextTests(Field\TextEditor::class);
        $this->infoTextTests(Field\TextEditor::class);
    }
    
    public function testActionProcesses()
    {
        $this->processIndexTests(Field\TextEditor::class);
        $this->processStoreTests(Field\TextEditor::class);
        $this->processUpdateTests(Field\TextEditor::class);
        $this->processShowTests(Field\TextEditor::class);
    }
    
    public function testProcessCreateEdit()
    {
        $field = Field\TextEditor::new(name: 'name')->setEntity(new Entity(['name' => 'Foo']));
        $field->processCreateEdit(action: Action\Edit::new(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<textarea data-editor=\'[]\' name="name" id="name">Foo</textarea>', $field->render());
        
        $field = Field\TextEditor::new(name: 'name')->setEntity(new Entity(['name' => ['en' => 'Foo']]))->translatable();
        $field->processCreateEdit(action: Action\Edit::new(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<textarea data-editor=\'[]\' name="name[en]" id="name_en">Foo</textarea>', $field->render());
    }
    
    public function testProcessIndexAction()
    {
        $field = Field\TextEditor::new(name: 'name')->setEntity(new Entity(['name' => '<h1>Lorem</h1><p>lorem   &nbsp;  ipsum</p>']));
        $field->processIndexAction(field: $field);
        $this->assertSame('Lorem lorem ipsum', $field->render());
    }
    
    public function testCustomAttributesAreRendered()
    {
        $field = Field\TextEditor::new(name: 'name')
            ->setEntity(new Entity(['name' => 'Foo']))
            ->attributes(['data-foo' => ['foo'], 'required']);
        
        $field->processCreateEdit(action: Action\Edit::new(), field: $field, view: Factory::createView());
        $this->assertStringContainsString(
            '<textarea data-foo=\'[&quot;foo&quot;]\' required data-editor=\'[]\' name="name" id="name">Foo</textarea>',
            $field->render()
        );
    }
    
    public function testCustomEditorConfig()
    {
        $field = Field\TextEditor::new(name: 'name')
            ->setEntity(new Entity(['name' => 'Foo']))
            ->editorConfig([
                'toolbar' => ['p', 'h1']
            ]);
        
        $field->processCreateEdit(action: Action\Edit::new(), field: $field, view: Factory::createView());
        $this->assertStringContainsString(
            '<textarea data-editor=\'{&quot;toolbar&quot;:[&quot;p&quot;,&quot;h1&quot;]}\' name="name" id="name">Foo</textarea>',
            $field->render()
        );
    }
}