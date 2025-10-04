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

class CheckboxesTest extends AbstractField
{
    public function testDefaultInterfaceMethods()
    {
        $field = new Field\Checkboxes(name: 'name');
        $this->assertInstanceof(Field\Checkboxes::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(new Field\Checkboxes(name: 'name'));
        $this->renderTests(new Field\Checkboxes(name: 'name'));
        $this->nameTests(Field\Checkboxes::class);
        $this->labelTests(Field\Checkboxes::class);
        $this->groupTests(Field\Checkboxes::class);
        $this->localeTests(new Field\Checkboxes(name: 'name'));
        $this->storableTests(new Field\Checkboxes(name: 'name'));
        $this->indexableTests(new Field\Checkboxes(name: 'name'));
        $this->creatableTests(new Field\Checkboxes(name: 'name'));
        $this->editableTests(new Field\Checkboxes(name: 'name'));
        $this->readonlyTests(new Field\Text(name: 'name'));
        $this->disabledTests(new Field\Text(name: 'name'));
        $this->entityTests(new Field\Checkboxes(name: 'name'));
        //$this->validateTests(Field\Checkboxes::class);
        $this->requiredTextTests(Field\Checkboxes::class, withTranslatable: false);
        $this->optionalTextTests(Field\Checkboxes::class, withTranslatable: false);
        $this->infoTextTests(Field\Checkboxes::class);
    }
    
    public function testActionProcesses()
    {
        $this->processStoreTests(Field\Checkboxes::class, withTranslatable: false);
        $this->processUpdateTests(Field\Checkboxes::class, withTranslatable: false);
        $this->processShowTests(Field\Checkboxes::class, withTranslatable: false);
    }
    
    public function testProcessIndex()
    {
        $field = new Field\Checkboxes(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['color' => ['red', 'blue']]));
        
        $field->processIndex(field: $field);
        
        $this->assertStringContainsString('Red, Blue', $field->render());
    }
    
    public function testProcessCreateEditUsingArrayOptions()
    {
        $field = new Field\Checkboxes(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $field->processCreateEdit(
            action: new Action\Edit(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<span class="wrap-v"><input id="name_1" name="name[]" type="checkbox" value="blue"><label for="name_1">Blue</label></span><span class="wrap-v"><input id="name_2" name="name[]" type="checkbox" value="red"><label for="name_2">Red</label></span><input name="name[]" type="hidden" value="_none">',
            $field->render()
        );
    }
    
    public function testProcessCreateEditUsingClosureOptions()
    {
        $field = new Field\Checkboxes(name: 'name')
            ->options(fn(CheckboxesColorRepo $repo): array => $repo->findColors());
        
        $action = new Action\Edit()->setFields(new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertStringContainsString(
            '<span class="wrap-v"><input id="name_1" name="name[]" type="checkbox" value="blue"><label for="name_1">Blue</label></span><span class="wrap-v"><input id="name_2" name="name[]" type="checkbox" value="red"><label for="name_2">Red</label></span><input name="name[]" type="hidden" value="_none">',
            $field->render()
        );
    }
    
    public function testProcessCreateEditSetsSelectedFromEntity()
    {
        $field = new Field\Checkboxes(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['name' => ['red']]));
        
        $field->processCreateEdit(
            action: new Action\Edit(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<span class="wrap-v"><input id="name_1" name="name[]" type="checkbox" value="blue"><label for="name_1">Blue</label></span><span class="wrap-v"><input id="name_2" name="name[]" type="checkbox" value="red" checked><label for="name_2">Red</label></span><input name="name[]" type="hidden" value="_none">',
            $field->render()
        );
    }
    
    public function testProcessCreateEditSetsSelectedFromSelected()
    {
        $field = new Field\Checkboxes(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->selected(value: ['red'], action: 'edit');
        
        $action = new Action\Edit()->setFields(new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertStringContainsString(
            '<span class="wrap-v"><input id="name_1" name="name[]" type="checkbox" value="blue"><label for="name_1">Blue</label></span><span class="wrap-v"><input id="name_2" name="name[]" type="checkbox" value="red" checked><label for="name_2">Red</label></span><input name="name[]" type="hidden" value="_none">',
            $field->render()
        );
    }
    
    public function testProcessCreateEditSetsSelectedFromSelectedUsingClosureReturningArray()
    {
        $field = new Field\Checkboxes(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->selected(
                value: function (ActionInterface $action, FieldInterface $field): null|array {
                    return ['red'];
                },
                action: 'edit',
            );
        
        $action = new Action\Edit()->setFields(new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertStringContainsString(
            '<span class="wrap-v"><input id="name_1" name="name[]" type="checkbox" value="blue"><label for="name_1">Blue</label></span><span class="wrap-v"><input id="name_2" name="name[]" type="checkbox" value="red" checked><label for="name_2">Red</label></span><input name="name[]" type="hidden" value="_none">',
            $field->render()
        );
    }
    
    public function testProcessCreateEditSetsSelectedFromSelectedUsingClosureReturningNull()
    {
        $field = new Field\Checkboxes(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->selected(
                value: function (ActionInterface $action, FieldInterface $field): null|array {
                    return null;
                },
                action: 'edit',
            );
        
        $field->processCreateEdit(
            action: new Action\Edit(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<span class="wrap-v"><input id="name_1" name="name[]" type="checkbox" value="blue"><label for="name_1">Blue</label></span><span class="wrap-v"><input id="name_2" name="name[]" type="checkbox" value="red"><label for="name_2">Red</label></span><input name="name[]" type="hidden" value="_none">',
            $field->render()
        );
    }
    
    public function testProcessCreateEdit()
    {
        $field = new Field\Checkboxes(name: 'option.color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->emptyOption(value: 'none')
            ->setEntity(new Entity(['option' => ['color' => ['red', 'blue']]]));
        
        $field->processCreateEdit(
            action: new Action\Edit(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<span class="wrap-v"><input id="option_color_1" name="option[color][]" type="checkbox" value="blue" checked><label for="option_color_1">Blue</label></span><span class="wrap-v"><input id="option_color_2" name="option[color][]" type="checkbox" value="red" checked><label for="option_color_2">Red</label></span><input name="option[color][]" type="hidden" value="none">',
            $field->render()
        );
    }
    
    public function testProcessSave()
    {
        $field = new Field\Checkboxes(name: 'color');
        $input = new Input(['color' => ['red']]);
        $field->processBeforeSave(field: $field, input: $input);
        $field->processSave(field: $field, input: $input);
        
        $this->assertSame(['color' => ['red']], $input->all());
    }
    
    public function testProcessSaveEmptyOptionIsRemoved()
    {
        $field = new Field\Checkboxes(name: 'color')->emptyOption(value: 'none');
        $input = new Input(['color' => ['none']]);
        $field->processBeforeSave(field: $field, input: $input);
        $field->processSave(field: $field, input: $input);
        
        $this->assertSame(['color' => []], $input->all());
    }
    
    public function testProcessShowAction()
    {
        $field = new Field\Checkboxes(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['color' => ['red']]));
        
        $field->processShow(field: $field, view: Factory::createView());
        $this->assertStringContainsString('Red', $field->render());
    }
    
    public function testValidatePasses()
    {
        $field = new Field\Checkboxes(name: 'color')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $validation = Factory::createValidator()->validate(
            data: ['color' => ['blue']],
            rules: $field->getValidationRulesForAction('create'),
        );
        
        $this->assertTrue($validation->isValid());
    }
    
    public function testValidateFailsIfInvalidOption()
    {
        $field = new Field\Checkboxes(name: 'color')->options(['blue' => 'Blue', 'red' => 'Red']);
        $rules = $field->getValidationRulesForAction('create');
        
        $validation = Factory::createValidator()->validate(
            data: ['color' => 'green'],
            rules: $rules,
        );
        
        $this->assertFalse($validation->isValid());
        
        $validation = Factory::createValidator()->validate(
            data: ['color' => ['green']],
            rules: $rules,
        );
        
        $this->assertFalse($validation->isValid());
    }
    
    public function testValidateResolvesOptionFromClosure()
    {
        $field = new Field\Checkboxes(name: 'color')
            ->options(fn(CheckboxesColorRepo $repo): array => $repo->findColors());
        
        $input = new Input(['color' => ['blue']]);
        $action = new Action\Update()
            ->setFields(new Field\Fields($field))
            ->setInput($input);
        
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $validation = Factory::createValidator()->validate(
            data: ['color' => ['blue']],
            rules: $field->getValidationRulesForAction('create'),
        );
        
        $this->assertTrue($validation->isValid());
    }
    
    public function testValidateRulesAreMerged()
    {
        $field = new Field\Checkboxes(name: 'color')->validate('required|minItems:2|maxItems:10');
        $rules = $field->getValidationRulesForAction('create');
        
        $this->assertSame('required|minItems:2|maxItems:10', $rules['color'][0] ?? null);
        $this->assertInstanceof(Rule\Passes::class, $rules['color'][1] ?? null);
    }
    
    public function testFormatValueIndexAction()
    {
        $field = new Field\Checkboxes(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['color' => ['red']]))
            ->formatValue(formatter: new Field\Formatter\CssClass('text-700'), action: 'index');
        
        $field->processIndex(field: $field);
        $this->assertStringContainsString('<span class="text-700">Red</span>', $field->render());
    }
    
    public function testFormatValueShowAction()
    {
        $field = new Field\Checkboxes(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['color' => ['red']]))
            ->formatValue(formatter: new Field\Formatter\CssClass('text-700'), action: 'show');
        
        $field->processShow(field: $field, view: Factory::createView());
        $this->assertStringContainsString('<span class="text-700">Red</span>', $field->render());
    }
}

class CheckboxesColorRepo
{
    public function findColors(): array
    {
        return ['blue' => 'Blue', 'red' => 'Red'];
    }
}