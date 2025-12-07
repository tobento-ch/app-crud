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
use Tobento\App\Crud\Button;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Test\Factory;

class ButtonsTest extends AbstractField
{
    public function testDefaultInterfaceMethods()
    {
        $field = new Field\Buttons(name: 'name');
        $this->assertInstanceof(Field\Buttons::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(new Field\Buttons(name: 'name'));
        $this->renderTests(new Field\Buttons(name: 'name'));
        $this->nameTests(Field\Buttons::class);
        $this->groupTests(Field\Buttons::class);
        $this->translatableTests(new Field\Buttons(name: 'name'));
        $this->localeTests(new Field\Buttons(name: 'name'));
        $this->assertFalse(new Field\Buttons(name: 'name')->isStorable());
        $this->assertFalse(new Field\Buttons(name: 'name')->isIndexable());
        $this->creatableTests(new Field\Buttons(name: 'name'));
        $this->editableTests(new Field\Buttons(name: 'name'));
        $this->entityTests(new Field\Buttons(name: 'name'));
        $this->validateTests(Field\Buttons::class);
        $this->requiredTextTests(Field\Buttons::class);
        $this->optionalTextTests(Field\Buttons::class);
        $this->infoTextTests(Field\Buttons::class);
    }
    
    public function testActionProcesses()
    {
        $this->processIndexTests(Field\Buttons::class);
        $this->processStoreTests(Field\Buttons::class);
        $this->processUpdateTests(Field\Buttons::class);
        $this->processShowTests(Field\Buttons::class);
    }
    
    public function testProcessRenderMethod()
    {
        $field = new Field\Buttons(name: 'name')
            ->buttons(
                new Button\Button(label: 'Save', group: 'entity')
                    ->name('save'),
                new Button\Button(label: 'Save & Close', group: 'entity')
                    ->name('close'),
            );
        
        $field->processRender(
            action: new Action\Edit(),
            field: $field,
            view: Factory::createView(),
            urlResolver: Factory::createActionProcessor()->urlResolver(),
        );
        
        $rendered = $field->render();
        $this->assertStringContainsString('<div data-field="name" class="left crud-buttons">', $rendered);
        $this->assertStringContainsString('<button class="button text-xs" data-button="save">Save</button>', $rendered);
    }
    
    public function testProcessRenderMethodAlignRight()
    {
        $field = new Field\Buttons(name: 'name')
            ->buttons(
                new Button\Button(label: 'Save', group: 'entity')
                    ->name('save'),
            )
            ->alignRight();
        
        $field->processRender(
            action: new Action\Edit(),
            field: $field,
            view: Factory::createView(),
            urlResolver: Factory::createActionProcessor()->urlResolver(),
        );
        
        $rendered = $field->render();
        $this->assertStringContainsString('<div data-field="name" class="right crud-buttons">', $rendered);
    }
    
    public function testProcessRenderMethodAlignCenter()
    {
        $field = new Field\Buttons(name: 'name')
            ->buttons(
                new Button\Button(label: 'Save', group: 'entity')
                    ->name('save'),
            )
            ->alignCenter();
        
        $field->processRender(
            action: new Action\Edit(),
            field: $field,
            view: Factory::createView(),
            urlResolver: Factory::createActionProcessor()->urlResolver(),
        );
        
        $rendered = $field->render();
        $this->assertStringContainsString('<div data-field="name" class="center crud-buttons">', $rendered);
    }
    
    public function testProcessRenderMethodDisplayAsField()
    {
        $field = new Field\Buttons(name: 'name')
            ->buttons(
                new Button\Button(label: 'Save', group: 'entity')
                    ->name('save'),
            )
            ->displayAsField();
        
        $field->processRender(
            action: new Action\Edit(),
            field: $field,
            view: Factory::createView(),
            urlResolver: Factory::createActionProcessor()->urlResolver(),
        );
        
        $rendered = $field->render();
        $this->assertStringContainsString('<div class="field field-crud" data-field="name">', $rendered);
        $this->assertStringContainsString('<div class="left crud-buttons">', $rendered);
    }

    public function testProcessRenderMethodWithHidden()
    {
        $field = new Field\Buttons(name: 'name')
            ->buttons(
                new Button\Button(label: 'Save', group: 'entity')
                    ->name('save'),
            )
            ->hidden();
        
        $field->processRender(
            action: new Action\Edit(),
            field: $field,
            view: Factory::createView(),
            urlResolver: Factory::createActionProcessor()->urlResolver(),
        );

        $this->assertSame('', $field->render());
    }
}