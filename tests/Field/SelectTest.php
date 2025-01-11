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

class SelectTest extends AbstractField
{
    public function testDefaultInterfaceMethods()
    {
        $field = Field\Select::new(name: 'name');
        $this->assertInstanceof(Field\Select::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(Field\Select::new(name: 'name'));
        $this->renderTests(Field\Select::new(name: 'name'));
        $this->nameTests(Field\Select::class);
        $this->labelTests(Field\Select::class);
        $this->groupTests(Field\Select::class);
        $this->localeTests(Field\Select::new(name: 'name'));
        $this->storableTests(Field\Select::new(name: 'name'));
        $this->indexableTests(Field\Select::new(name: 'name'));
        $this->creatableTests(Field\Select::new(name: 'name'));
        $this->editableTests(Field\Select::new(name: 'name'));
        $this->readonlyTests(Field\Text::new(name: 'name'));
        $this->disabledTests(Field\Text::new(name: 'name'));
        $this->entityTests(Field\Select::new(name: 'name'));
        //$this->validateTests(Field\Select::class);
        $this->requiredTextTests(Field\Select::class, withTranslatable: false);
        $this->optionalTextTests(Field\Select::class, withTranslatable: false);
        $this->infoTextTests(Field\Select::class);
    }
    
    public function testActionProcesses()
    {
        $this->processIndexTests(Field\Select::class, withTranslatable: false);
        $this->processStoreTests(Field\Select::class, withTranslatable: false);
        $this->processUpdateTests(Field\Select::class, withTranslatable: false);
        $this->processShowTests(Field\Select::class, withTranslatable: false);
    }
    
    public function testProcessIndex()
    {
        $field = Field\Select::new(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['color' => 'red']));
        
        $field->processIndex(field: $field);
        
        $this->assertStringContainsString('red', $field->render());
    }
    
    public function testProcessIndexMultiple()
    {
        $field = Field\Select::new(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->attributes(['multiple', 'size' => '10'])
            ->setEntity(new Entity(['color' => ['red', 'blue']]));
        
        $field->processIndex(field: $field);
        
        $this->assertStringContainsString('red, blue', $field->render());
    }
    
    public function testProcessCreateEditUsingArrayOptions()
    {
        $field = Field\Select::new(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $field->processCreateEdit(
            action: Action\Edit::new(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<select id="name" name="name"><option value="blue">Blue</option><option value="red">Red</option></select>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditUsingClosureOptions()
    {
        $field = Field\Select::new(name: 'name')
            ->options(fn(ColorRepo $repo): array => $repo->findColors());
        
        $action = Action\Edit::new()->setFields(new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertStringContainsString(
            '<select id="name" name="name"><option value="blue">Blue</option><option value="red">Red</option></select>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditUsingArrayOptionsAsGroup()
    {
        $field = Field\Select::new(name: 'name')
            ->options([
                'Frontend' => [
                    'guest' => 'Guest',
                ],
                'Backend' => [
                    'editor' => 'Editor',
                ],
            ]);
        
        $field->processCreateEdit(
            action: Action\Edit::new(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<select id="name" name="name"><optgroup label="Frontend"><option value="guest">Guest</option></optgroup><optgroup label="Backend"><option value="editor">Editor</option></optgroup></select>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditWithEmptyOption()
    {
        $field = Field\Select::new(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->emptyOption(value: 'none', label: '---');
        
        $field->processCreateEdit(
            action: Action\Edit::new(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<select id="name" name="name"><option value="none">---</option><option value="blue">Blue</option><option value="red">Red</option></select>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditSetsSelectedFromEntity()
    {
        $field = Field\Select::new(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['name' => ['red']]));
        
        $field->processCreateEdit(
            action: Action\Edit::new(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<select id="name" name="name"><option value="blue">Blue</option><option value="red" selected>Red</option></select>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditSetsSelectedFromSelected()
    {
        $field = Field\Select::new(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->selected(value: 'red', action: 'edit');
        
        $action = Action\Edit::new()->setFields(new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertStringContainsString(
            '<select id="name" name="name"><option value="blue">Blue</option><option value="red" selected>Red</option></select>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditSetsSelectedFromSelectedUsingClosureReturningArray()
    {
        $field = Field\Select::new(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->selected(
                value: function (ActionInterface $action, FieldInterface $field): null|string|array {
                    return ['red'];
                },
                action: 'edit',
            );
        
        $action = Action\Edit::new()->setFields(new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertStringContainsString(
            '<select id="name" name="name"><option value="blue">Blue</option><option value="red" selected>Red</option></select>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditSetsSelectedFromSelectedUsingClosureReturningString()
    {
        $field = Field\Select::new(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->selected(
                value: function (ActionInterface $action, FieldInterface $field): null|string|array {
                    return 'red';
                },
                action: 'edit',
            );
        
        $action = Action\Edit::new()->setFields(new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertStringContainsString(
            '<select id="name" name="name"><option value="blue">Blue</option><option value="red" selected>Red</option></select>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditSetsSelectedFromSelectedUsingClosureReturningNull()
    {
        $field = Field\Select::new(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->selected(
                value: function (ActionInterface $action, FieldInterface $field): null|string|array {
                    return null;
                },
                action: 'edit',
            );
        
        $action = Action\Edit::new()->setFields(new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertStringContainsString(
            '<select id="name" name="name"><option value="blue">Blue</option><option value="red">Red</option></select>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditMultiple()
    {
        $field = Field\Select::new(name: 'option.color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->attributes(['multiple', 'size' => '10'])
            ->setEntity(new Entity(['option' => ['color' => ['red', 'blue']]]));
        
        $field->processCreateEdit(
            action: Action\Edit::new(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<select multiple size="10" id="option_color" name="option[color][]"><option value="blue" selected>Blue</option><option value="red" selected>Red</option></select>',
            $field->render()
        );
    }
    
    public function testProcessSave()
    {
        $field = Field\Select::new(name: 'color');
        $input = new Input(['color' => 'red']);
        $field->processSave(field: $field, input: $input);
        
        $this->assertSame(['color' => 'red'], $input->all());
    }
    
    public function testProcessSaveEmptyOptionIsRemoved()
    {
        $field = Field\Select::new(name: 'color')->emptyOption(value: 'none', label: '---');
        $input = new Input(['color' => 'none']);
        $field->processSave(field: $field, input: $input);
        
        $this->assertSame(['color' => ''], $input->all());
    }
    
    public function testProcessSaveMultiple()
    {
        $field = Field\Select::new(name: 'color')->attributes(['multiple']);
        $input = new Input(['color' => ['blue', 'red']]);
        $field->processSave(field: $field, input: $input);
        
        $this->assertSame(['color' => ['blue', 'red']], $input->all());
    }
    
    public function testProcessSaveMultipleEmptyOptionIsRemoved()
    {
        $field = Field\Select::new(name: 'color')->attributes(['multiple'])->emptyOption(value: 'none', label: '---');
        $input = new Input(['color' => ['blue', 'none']]);
        $field->processSave(field: $field, input: $input);
        
        $this->assertSame(['color' => ['blue']], $input->all());
    }
    
    public function testValidatePasses()
    {
        $field = Field\Select::new(name: 'color')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $validation = Factory::createValidator()->validate(
            data: ['color' => 'blue'],
            rules: $field->getValidationRulesForAction('create'),
        );
        
        $this->assertTrue($validation->isValid());
    }
    
    public function testValidatePassesWithEmptyOption()
    {
        $field = Field\Select::new(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->emptyOption(value: 'none', label: '---');
        
        $validation = Factory::createValidator()->validate(
            data: ['color' => 'none'],
            rules: $field->getValidationRulesForAction('create'),
        );
        
        $this->assertTrue($validation->isValid());
    }    
    
    public function testValidateFailsIfInvalidOption()
    {
        $field = Field\Select::new(name: 'color')->options(['blue' => 'Blue', 'red' => 'Red']);
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
    
    public function testValidateMultiplePasses()
    {
        $field = Field\Select::new(name: 'color')
            ->attributes(['multiple'])
            ->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $validation = Factory::createValidator()->validate(
            data: ['color' => ['blue', 'red']],
            rules: $field->getValidationRulesForAction('create'),
        );
        
        $this->assertTrue($validation->isValid());
    }
    
    public function testValidateMultiplePassesWithEmptyOption()
    {
        $field = Field\Select::new(name: 'color')
            ->attributes(['multiple'])
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->emptyOption(value: 'none', label: '---');
        
        $validation = Factory::createValidator()->validate(
            data: ['color' => ['blue', 'none']],
            rules: $field->getValidationRulesForAction('create'),
        );
        
        $this->assertTrue($validation->isValid());
    }    
    
    public function testValidateMultipleFailsIfInvalidOption()
    {
        $field = Field\Select::new(name: 'color')
            ->attributes(['multiple'])
            ->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $rules = $field->getValidationRulesForAction('create');
        
        $validation = Factory::createValidator()->validate(
            data: ['color' => ['blue', 'green']],
            rules: $rules,
        );
        
        $this->assertFalse($validation->isValid());
        
        $validation = Factory::createValidator()->validate(
            data: ['color' => 'blue'],
            rules: $rules,
        );
        
        $this->assertFalse($validation->isValid());
    }
    
    public function testValidateResolvesOptionFromClosure()
    {
        $field = Field\Select::new(name: 'color')
            ->attributes(['multiple'])
            ->options(fn(ColorRepo $repo): array => $repo->findColors());
        
        $action = Action\Edit::new()->setFields(new Field\Fields($field));
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
        $field = Field\Select::new(name: 'color')->validate('required|minItems:2|maxItems:10');
        $rules = $field->getValidationRulesForAction('create');
        
        $this->assertSame('required|minItems:2|maxItems:10', $rules['color'][0] ?? null);
        $this->assertInstanceof(Rule\Passes::class, $rules['color'][1] ?? null);
    }
}

class ColorRepo
{
    public function findColors(): array
    {
        return ['blue' => 'Blue', 'red' => 'Red'];
    }
}