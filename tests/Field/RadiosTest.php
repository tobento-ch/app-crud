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
use Tobento\App\Crud\Field;
use Tobento\App\Crud\new Field\FieldInterface;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Test\Factory;
use Tobento\Service\Container\Container;
use Tobento\Service\Validation\Rule;

class RadiosTest extends AbstractField
{
    public function testDefaultInterfaceMethods()
    {
        $field = new Field\Radios(name: 'name');
        $this->assertInstanceof(new Field\Radios::class, $field);
        $this->assertInstanceof(new Field\FieldInterface::class, $field);
        
        $this->processTests(new Field\Radios(name: 'name'));
        $this->renderTests(new Field\Radios(name: 'name'));
        $this->nameTests(new Field\Radios::class);
        $this->labelTests(new Field\Radios::class);
        $this->groupTests(new Field\Radios::class);
        $this->localeTests(new Field\Radios(name: 'name'));
        $this->storableTests(new Field\Radios(name: 'name'));
        $this->indexableTests(new Field\Radios(name: 'name'));
        $this->creatableTests(new Field\Radios(name: 'name'));
        $this->editableTests(new Field\Radios(name: 'name'));
        $this->readonlyTests(new Field\Text(name: 'name'));
        $this->disabledTests(new Field\Text(name: 'name'));
        $this->entityTests(new Field\Radios(name: 'name'));
        //$this->validateTests(new Field\Radios::class);
        $this->requiredTextTests(new Field\Radios::class, withTranslatable: false);
        $this->optionalTextTests(new Field\Radios::class, withTranslatable: false);
        $this->infoTextTests(new Field\Radios::class);
    }
    
    public function testActionProcesses()
    {
        $this->processStoreTests(new Field\Radios::class, withTranslatable: false);
        $this->processUpdateTests(new Field\Radios::class, withTranslatable: false);
        $this->processShowTests(new Field\Radios::class, withTranslatable: false);
    }
    
    public function testProcessIndex()
    {
        $field = new Field\Radios(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['color' => 'red']));
        
        $field->processIndex(field: $field);
        
        $this->assertStringContainsString('Red', $field->render());
    }
    
    public function testProcessCreateEditUsingArrayOptions()
    {
        $field = new Field\Radios(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $field->processCreateEdit(
            action: Action\Edit(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<span class="wrap-v"><input id="name_1" name="name" type="radio" value="blue"><label for="name_1">Blue</label></span><span class="wrap-v"><input id="name_2" name="name" type="radio" value="red"><label for="name_2">Red</label></span>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditUsingClosureOptions()
    {
        $field = new Field\Radios(name: 'name')
            ->options(fn(RadiosColorRepo $repo): array => $repo->findColors());
        
        $action = Action\Edit()->setFields(new new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertStringContainsString(
            '<span class="wrap-v"><input id="name_1" name="name" type="radio" value="blue"><label for="name_1">Blue</label></span><span class="wrap-v"><input id="name_2" name="name" type="radio" value="red"><label for="name_2">Red</label></span>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditSetsSelectedFromEntity()
    {
        $field = new Field\Radios(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['name' => 'red']));
        
        $field->processCreateEdit(
            action: Action\Edit(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<span class="wrap-v"><input id="name_1" name="name" type="radio" value="blue"><label for="name_1">Blue</label></span><span class="wrap-v"><input id="name_2" name="name" type="radio" value="red" checked><label for="name_2">Red</label></span>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditSetsSelectedFromSelected()
    {
        $field = new Field\Radios(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->selected(value: 'red', action: 'edit');
        
        $action = Action\Edit()->setFields(new new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertStringContainsString(
            '<span class="wrap-v"><input id="name_1" name="name" type="radio" value="blue"><label for="name_1">Blue</label></span><span class="wrap-v"><input id="name_2" name="name" type="radio" value="red" checked><label for="name_2">Red</label></span>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditSetsSelectedFromSelectedUsingClosureReturningString()
    {
        $field = new Field\Radios(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->selected(
                value: function (ActionInterface $action, FieldInterface $field): null|string {
                    return 'red';
                },
                action: 'edit',
            );
        
        $action = Action\Edit()->setFields(new new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertStringContainsString(
            '<span class="wrap-v"><input id="name_1" name="name" type="radio" value="blue"><label for="name_1">Blue</label></span><span class="wrap-v"><input id="name_2" name="name" type="radio" value="red" checked><label for="name_2">Red</label></span>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditSetsSelectedFromSelectedUsingClosureReturningNull()
    {
        $field = new Field\Radios(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->selected(
                value: function (ActionInterface $action, FieldInterface $field): null|string {
                    return null;
                },
                action: 'edit',
            );
        
        $action = Action\Edit()->setFields(new new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertStringContainsString(
            '<span class="wrap-v"><input id="name_1" name="name" type="radio" value="blue"><label for="name_1">Blue</label></span><span class="wrap-v"><input id="name_2" name="name" type="radio" value="red"><label for="name_2">Red</label></span>',
            $field->render()
        );
    }
    
    public function testProcessCreateEdit()
    {
        $field = new Field\Radios(name: 'option.color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['option' => ['color' => 'red']]));
        
        $field->processCreateEdit(
            action: Action\Edit(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<span class="wrap-v"><input id="option_color_1" name="option[color]" type="radio" value="blue"><label for="option_color_1">Blue</label></span><span class="wrap-v"><input id="option_color_2" name="option[color]" type="radio" value="red" checked><label for="option_color_2">Red</label></span>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditDisplayInline()
    {
        $field = new Field\Radios(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->displayInline()
            ->setEntity(new Entity(['color' => 'red']));
        
        $field->processCreateEdit(
            action: Action\Edit(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<span class="wrap-h"><input id="color_1" name="color" type="radio" value="blue"><label for="color_1">Blue</label></span><span class="wrap-h"><input id="color_2" name="color" type="radio" value="red" checked><label for="color_2">Red</label></span>',
            $field->render()
        );
    }
    
    public function testProcessSave()
    {
        $field = new Field\Radios(name: 'color');
        $input = new Input(['color' => 'red']);
        $field->processSave(field: $field, input: $input);
        
        $this->assertSame(['color' => 'red'], $input->all());
    }
    
    public function testProcessShowAction()
    {
        $field = new Field\Radios(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['color' => 'red']));
        
        $field->processShow(field: $field, view: Factory::createView());
        $this->assertStringContainsString('Red', $field->render());
    }
    
    public function testValidatePasses()
    {
        $field = new Field\Radios(name: 'color')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $validation = Factory::createValidator()->validate(
            data: ['color' => 'blue'],
            rules: $field->getValidationRulesForAction('create'),
        );
        
        $this->assertTrue($validation->isValid());
    }
    
    public function testValidateFailsIfInvalidOption()
    {
        $field = new Field\Radios(name: 'color')->options(['blue' => 'Blue', 'red' => 'Red']);
        $rules = $field->getValidationRulesForAction('create');
        
        $validation = Factory::createValidator()->validate(
            data: ['color' => 'green'],
            rules: $rules,
        );
        
        $this->assertFalse($validation->isValid());
        
        $validation = Factory::createValidator()->validate(
            data: ['color' => []],
            rules: $rules,
        );
        
        $this->assertFalse($validation->isValid());
    }
    
    public function testValidateResolvesOptionFromClosure()
    {
        $field = new Field\Radios(name: 'color')
            ->options(fn(RadiosColorRepo $repo): array => $repo->findColors());
        
        $input = new Input(['color' => 'blue']);
        $action = Action\Update()
            ->setFields(new new Field\Fields($field))
            ->setInput($input);
        
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        $rules = $field->getValidationRulesForAction('create');
        
        $validation = Factory::createValidator()->validate(
            data: ['color' => 'blue'],
            rules: $rules,
        );
        
        $this->assertTrue($validation->isValid());
        
        $validation = Factory::createValidator()->validate(
            data: ['color' => 'yellow'],
            rules: $rules,
        );
        
        $this->assertFalse($validation->isValid());
    }
    
    public function testValidateRulesAreMerged()
    {
        $field = new Field\Radios(name: 'color')->validate('required');
        $rules = $field->getValidationRulesForAction('create');
        
        $this->assertSame('required', $rules['color'][0] ?? null);
        $this->assertInstanceof(Rule\Passes::class, $rules['color'][1] ?? null);
    }
    
    public function testFormatValueIndexAction()
    {
        $field = new Field\Radios(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['color' => 'red']))
            ->formatValue(formatter: new new Field\Formatter\CssClass('text-700'), action: 'index');
        
        $field->processIndex(field: $field);
        $this->assertStringContainsString('<span class="text-700">Red</span>', $field->render());
    }
    
    public function testFormatValueShowAction()
    {
        $field = new Field\Radios(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['color' => 'red']))
            ->formatValue(formatter: new new Field\Formatter\CssClass('text-700'), action: 'show');
        
        $field->processShow(field: $field, view: Factory::createView());
        $this->assertStringContainsString('<span class="text-700">Red</span>', $field->render());
    }
}

class RadiosColorRepo
{
    public function findColors(): array
    {
        return ['blue' => 'Blue', 'red' => 'Red'];
    }
}