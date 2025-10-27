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
        $field = new Field\Select(name: 'name');
        $this->assertInstanceof(Field\Select::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(new Field\Select(name: 'name'));
        $this->renderTests(new Field\Select(name: 'name'));
        $this->nameTests(Field\Select::class);
        $this->labelTests(Field\Select::class);
        $this->groupTests(Field\Select::class);
        $this->localeTests(new Field\Select(name: 'name'));
        $this->storableTests(new Field\Select(name: 'name'));
        $this->indexableTests(new Field\Select(name: 'name'));
        $this->creatableTests(new Field\Select(name: 'name'));
        $this->editableTests(new Field\Select(name: 'name'));
        $this->readonlyTests(new Field\Text(name: 'name'));
        $this->disabledTests(new Field\Text(name: 'name'));
        $this->entityTests(new Field\Select(name: 'name'));
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
        $field = new Field\Select(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['color' => 'red']));
        
        $field->processIndex(field: $field);
        
        $this->assertStringContainsString('Red', $field->render());
    }
    
    public function testProcessIndexMultiple()
    {
        $field = new Field\Select(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->attributes(['multiple', 'size' => '10'])
            ->setEntity(new Entity(['color' => ['red', 'blue']]));
        
        $field->processIndex(field: $field);
        
        $this->assertStringContainsString('Red, Blue', $field->render());
    }
    
    public function testProcessCreateEditUsingArrayOptions()
    {
        $field = new Field\Select(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $field->processCreateEdit(
            action: new Action\Edit(),
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
        $field = new Field\Select(name: 'name')
            ->options(fn(ColorRepo $repo): array => $repo->findColors());
        
        $action = new Action\Edit()->setFields(new Field\Fields($field));
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
        $field = new Field\Select(name: 'name')
            ->options([
                'Frontend' => [
                    'guest' => 'Guest',
                ],
                'Backend' => [
                    'editor' => 'Editor',
                ],
            ]);
        
        $field->processCreateEdit(
            action: new Action\Edit(),
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
        $field = new Field\Select(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->emptyOption(value: 'none', label: '---');
        
        $field->processCreateEdit(
            action: new Action\Edit(),
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
        $field = new Field\Select(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['name' => ['red']]));
        
        $field->processCreateEdit(
            action: new Action\Edit(),
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
        $field = new Field\Select(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->selected(value: 'red', action: 'edit');
        
        $action = new Action\Edit()->setFields(new Field\Fields($field));
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
        $field = new Field\Select(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->selected(
                value: function (ActionInterface $action, FieldInterface $field): null|string|array {
                    return ['red'];
                },
                action: 'edit',
            );
        
        $action = new Action\Edit()->setFields(new Field\Fields($field));
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
        $field = new Field\Select(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->selected(
                value: function (ActionInterface $action, FieldInterface $field): null|string|array {
                    return 'red';
                },
                action: 'edit',
            );
        
        $action = new Action\Edit()->setFields(new Field\Fields($field));
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
        $field = new Field\Select(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->selected(
                value: function (ActionInterface $action, FieldInterface $field): null|string|array {
                    return null;
                },
                action: 'edit',
            );
        
        $action = new Action\Edit()->setFields(new Field\Fields($field));
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
        $field = new Field\Select(name: 'option.color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->attributes(['multiple', 'size' => '10'])
            ->setEntity(new Entity(['option' => ['color' => ['red', 'blue']]]));
        
        $field->processCreateEdit(
            action: new Action\Edit(),
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
        $field = new Field\Select(name: 'color');
        $input = new Input(['color' => 'red']);
        $field->processSave(field: $field, input: $input);
        
        $this->assertSame(['color' => 'red'], $input->all());
    }
    
    public function testProcessSaveEmptyOptionIsRemoved()
    {
        $field = new Field\Select(name: 'color')->emptyOption(value: 'none', label: '---');
        $input = new Input(['color' => 'none']);
        $field->processSave(field: $field, input: $input);
        
        $this->assertSame(['color' => ''], $input->all());
    }
    
    public function testProcessSaveMultiple()
    {
        $field = new Field\Select(name: 'color')->attributes(['multiple']);
        $input = new Input(['color' => ['blue', 'red']]);
        $field->processSave(field: $field, input: $input);
        
        $this->assertSame(['color' => ['blue', 'red']], $input->all());
    }
    
    public function testProcessSaveMultipleEmptyOptionIsRemoved()
    {
        $field = new Field\Select(name: 'color')->attributes(['multiple'])->emptyOption(value: 'none', label: '---');
        $input = new Input(['color' => ['blue', 'none']]);
        $field->processSave(field: $field, input: $input);
        
        $this->assertSame(['color' => ['blue']], $input->all());
    }
    
    public function testProcessShowAction()
    {
        $field = new Field\Select(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['color' => 'red']));
        
        $field->processShow(field: $field, view: Factory::createView());
        $this->assertStringContainsString('Red', $field->render());
    }
    
    public function testProcessShowActionMuliple()
    {
        $field = new Field\Select(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->attributes(['multiple', 'size' => '10'])
            ->setEntity(new Entity(['color' => ['blue', 'red']]));
        
        $field->processShow(field: $field, view: Factory::createView());
        $this->assertStringContainsString('Blue, Red', $field->render());
    }
    
    public function testValidatePasses()
    {
        $field = new Field\Select(name: 'color')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $validation = Factory::createValidator()->validate(
            data: ['color' => 'blue'],
            rules: $field->getValidationRulesForAction('create'),
        );
        
        $this->assertTrue($validation->isValid());
    }
    
    public function testValidatePassesWithEmptyOption()
    {
        $field = new Field\Select(name: 'color')
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
        $field = new Field\Select(name: 'color')->options(['blue' => 'Blue', 'red' => 'Red']);
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
        $field = new Field\Select(name: 'color')
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
        $field = new Field\Select(name: 'color')
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
        $field = new Field\Select(name: 'color')
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
        $field = new Field\Select(name: 'color')
            ->attributes(['multiple'])
            ->options(fn(ColorRepo $repo): array => $repo->findColors());
        
        $action = new Action\Edit()->setFields(new Field\Fields($field));
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
        $field = new Field\Select(name: 'color')->validate('required|minItems:2|maxItems:10');
        $rules = $field->getValidationRulesForAction('create');
        
        $this->assertSame('required|minItems:2|maxItems:10', $rules['color'][0] ?? null);
        $this->assertInstanceof(Rule\Passes::class, $rules['color'][1] ?? null);
    }
    
    public function testOptionAttributesAreRendered()
    {
        $field = new Field\Select(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->optionAttributes([
                '*' => ['data-all' => 'value'],
                'blue' => ['data-blue' => 'value'],
            ]);
        
        $field->processCreateEdit(action: new Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString(
            '<select id="color" name="color"><option data-all="value" data-blue="value" value="blue">Blue</option><option data-all="value" value="red">Red</option></select>',
            $field->render()
        );
    }
    
    public function testOptgroupAttributesAreRendered()
    {
        $field = new Field\Select(name: 'color')
            ->options([
                'Frontend' => [
                    'guest' => 'Guest',
                ],
                'Backend' => [
                    'editor' => 'Editor',
                ],
            ])
            ->optgroupAttributes(['data-foo' => 'value']);
        
        $field->processCreateEdit(action: new Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString(
            '<select id="color" name="color"><optgroup data-foo="value" label="Frontend"><option value="guest">Guest</option></optgroup><optgroup data-foo="value" label="Backend"><option value="editor">Editor</option></optgroup></select>',
            $field->render()
        );
    }
    
    public function testFormatValueIndexAction()
    {
        $field = new Field\Select(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['color' => 'red']))
            ->formatValue(formatter: new Field\Formatter\CssClass('text-700'), action: 'index');
        
        $field->processIndex(field: $field);
        $this->assertStringContainsString('<span class="text-700">Red</span>', $field->render());
    }
    
    public function testFormatValueShowAction()
    {
        $field = new Field\Select(name: 'color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->setEntity(new Entity(['color' => 'red']))
            ->formatValue(formatter: new Field\Formatter\CssClass('text-700'), action: 'show');
        
        $field->processShow(field: $field, view: Factory::createView());
        $this->assertStringContainsString('<span class="text-700">Red</span>', $field->render());
    }
    
    public function testLiveFeature()
    {
        $field = new Field\Select(name: 'name')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->live();
        
        $field->processCreateEdit(
            action: new Action\Edit(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<select id="name" data-live=\'{&quot;fields&quot;:[],&quot;selectors&quot;:[],&quot;blur&quot;:false,&quot;debounce&quot;:0}\' name="name">',
            $field->render()
        );
        
        $this->assertInstanceof(\Tobento\App\Crud\Field\LiveAwareInterface::class, $field);
    }
}

class ColorRepo
{
    public function findColors(): array
    {
        return ['blue' => 'Blue', 'red' => 'Red'];
    }
}