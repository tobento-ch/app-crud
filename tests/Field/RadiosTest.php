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
use Tobento\App\Crud\Field\FieldInterface;
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
        $this->assertInstanceof(Field\Radios::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(new Field\Radios(name: 'name'));
        $this->renderTests(new Field\Radios(name: 'name'));
        $this->nameTests(Field\Radios::class);
        $this->labelTests(Field\Radios::class);
        $this->groupTests(Field\Radios::class);
        $this->localeTests(new Field\Radios(name: 'name'));
        $this->storableTests(new Field\Radios(name: 'name'));
        $this->indexableTests(new Field\Radios(name: 'name'));
        $this->creatableTests(new Field\Radios(name: 'name'));
        $this->editableTests(new Field\Radios(name: 'name'));
        $this->readonlyTests(new Field\Text(name: 'name'));
        $this->disabledTests(new Field\Text(name: 'name'));
        $this->entityTests(new Field\Radios(name: 'name'));
        //$this->validateTests(Field\Radios::class);
        $this->requiredTextTests(Field\Radios::class, withTranslatable: false);
        $this->optionalTextTests(Field\Radios::class, withTranslatable: false);
        $this->infoTextTests(Field\Radios::class);
    }
    
    public function testActionProcesses()
    {
        $this->processStoreTests(Field\Radios::class, withTranslatable: false);
        $this->processUpdateTests(Field\Radios::class, withTranslatable: false);
        $this->processShowTests(Field\Radios::class, withTranslatable: false);
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
            action: new Action\Edit(),
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
        
        $action = new Action\Edit()->setFields(new Field\Fields($field));
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
            action: new Action\Edit(),
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
        
        $action = new Action\Edit()->setFields(new Field\Fields($field));
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
        
        $action = new Action\Edit()->setFields(new Field\Fields($field));
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
        
        $action = new Action\Edit()->setFields(new Field\Fields($field));
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
            action: new Action\Edit(),
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
            action: new Action\Edit(),
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
        $action = new Action\Update()
            ->setFields(new Field\Fields($field))
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
            ->formatValue(formatter: new Field\Formatter\CssClass('text-700'), action: 'index');
        
        $field->processIndex(field: $field);
        $this->assertStringContainsString('<span class="text-700">Red</span>', $field->render());
    }
    
    public function testFormatValueShowAction()
    {
        $field = new Field\Radios(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['color' => 'red']))
            ->formatValue(formatter: new Field\Formatter\CssClass('text-700'), action: 'show');
        
        $field->processShow(field: $field, view: Factory::createView());
        $this->assertStringContainsString('<span class="text-700">Red</span>', $field->render());
    }
    
    public function testLiveFeature()
    {
        $field = new Field\Radios(name: 'name')
            ->options(['blue' => 'Blue'])
            ->live();
        
        $field->processCreateEdit(
            action: new Action\Edit(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<span class="wrap-v"><input id="name_1" data-live=\'{&quot;fields&quot;:[],&quot;selectors&quot;:[],&quot;blur&quot;:false,&quot;debounce&quot;:0}\' name="name" type="radio" value="blue"><label for="name_1">Blue</label></span>',
            $field->render()
        );
        
        $this->assertInstanceof(\Tobento\App\Crud\Field\LiveAwareInterface::class, $field);
    }
}

class RadiosColorRepo
{
    public function findColors(): array
    {
        return ['blue' => 'Blue', 'red' => 'Red'];
    }
}