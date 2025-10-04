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
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Validation\Rule;
use Tobento\Service\View\ViewInterface;

class OptionsTest extends AbstractField
{
    protected function createRepository(): RepositoryInterface
    {
        return Factory::createStorageRepository(
            table: 'users',
            columns: [
                Column\Id(),
                Column\Text('name'),
                Column\Text('type'),
            ],
        );
    }
    
    public function testDefaultInterfaceMethods()
    {
        $field = new Field\Options(name: 'name');
        $this->assertInstanceof(new Field\Options::class, $field);
        $this->assertInstanceof(new Field\FieldInterface::class, $field);
        
        $this->processTests(new Field\Options(name: 'name'));
        $this->renderTests(new Field\Options(name: 'name'));
        $this->nameTests(new Field\Options::class);
        $this->labelTests(new Field\Options::class);
        $this->groupTests(new Field\Options::class);
        $this->localeTests(new Field\Options(name: 'name'));
        $this->storableTests(new Field\Options(name: 'name'));
        $this->indexableTests(new Field\Options(name: 'name'));
        $this->creatableTests(new Field\Options(name: 'name'));
        $this->editableTests(new Field\Options(name: 'name'));
        $this->readonlyTests(new Field\Text(name: 'name'));
        $this->disabledTests(new Field\Text(name: 'name'));
        $this->entityTests(new Field\Options(name: 'name'));
        $this->requiredTextTests(new Field\Options::class, withTranslatable: false);
        $this->optionalTextTests(new Field\Options::class, withTranslatable: false);
        $this->infoTextTests(new Field\Options::class);
    }
    
    public function testActionProcesses()
    {
        $this->processStoreTests(new Field\Options::class, withTranslatable: false);
        $this->processUpdateTests(new Field\Options::class, withTranslatable: false);
        //$this->processShowTests(new Field\Options::class, withTranslatable: false);
    }
    
    public function testProcessIndex()
    {
        $field = new Field\Options(name: 'color')
            ->setEntity(new Entity(['color' => ['red', 'blue']]));
        
        $field->processIndex(field: $field);
        
        $this->assertStringContainsString('red, blue', $field->render());
    }
    
    public function testProcessShow()
    {
        $repo = $this->createRepository();
        $repo->create(['name' => 'red']);
        $repo->create(['name' => 'blue']);
        
        $field = new Field\Options(name: 'color')
            ->repository($repo)
            ->toOption(function(object $item, ViewInterface $view, new Field\Options $options): new Field\Option {        
                return new new Field\Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('name'),
                );
            })
            ->setEntity(new Entity(['color' => ['1', '2']]));

        $field->processShow(field: $field, view: Factory::createView());
        $this->assertStringContainsString('red, blue', $field->render());
    }
    
    public function testProcessCreateEditRendersSearchInput()
    {
        $field = new Field\Options(name: 'foo.bar')
            ->repository($this->createRepository());
        
        $field->processCreateEdit(
            action: Action\Edit(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<input data-options-action="search" aria-label="Search" placeholder name="search[foo][bar]" id="search_foo_bar" type="search" value>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditRendersEmptyOption()
    {
        $field = new Field\Options(name: 'foo')
            ->repository($this->createRepository());
        
        $field->processCreateEdit(
            action: Action\Edit(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<input name="foo[]" type="hidden" value="_none">',
            $field->render()
        );
    }    
    
    public function testProcessCreateEditRendersSearchInputWithPlaceholder()
    {
        $field = new Field\Options(name: 'foo.bar')
            ->repository($this->createRepository())
            ->placeholder('text');
        
        $field->processCreateEdit(
            action: Action\Edit(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<input data-options-action="search" aria-label="Search" placeholder="text" name="search[foo][bar]" id="search_foo_bar" type="search" value>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditSetsSelectedFromEntity()
    {
        $repo = $this->createRepository();
        $repo->create(['name' => 'foo']);
        $repo->create(['name' => 'bar']);
        
        $field = new Field\Options(name: 'name')
            ->repository($repo)
            ->toOption(function(object $item, ViewInterface $view, new Field\Options $options): new Field\Option {        
                return new new Field\Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('name'),
                );
            })
            ->setEntity(new Entity(['name' => ['1', '2']]));
        
        $field->processCreateEdit(
            action: Action\Edit(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<input name="name[]" type="checkbox" value="1" checked>',
            $field->render()
        );
        
        $this->assertStringContainsString(
            '<input name="name[]" type="checkbox" value="2" checked>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditSetsSelectedFromSelected()
    {
        $repo = $this->createRepository();
        $repo->create(['name' => 'foo']);
        
        $field = new Field\Options(name: 'name')
            ->repository($repo)
            ->toOption(function(object $item, ViewInterface $view, new Field\Options $options): new Field\Option {        
                return new new Field\Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('name'),
                );
            })
            ->selected(value: ['1'], action: 'edit');
        
        $action = Action\Edit()->setFields(new new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertStringContainsString(
            '<input name="name[]" type="checkbox" value="1" checked>',
            $field->render()
        );
    }
    
    public function testProcessCreateEditRendersUnselectedFromSearch()
    {
        $repo = $this->createRepository();
        $repo->create(['name' => 'foo']);
        
        $field = new Field\Options(name: 'name')
            ->repository($repo)
            ->searchColumns('name')
            ->toOption(function(object $item, ViewInterface $view, new Field\Options $options): new Field\Option {        
                return new new Field\Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('name'),
                );
            })
            ->setEntity(new Entity(['name' => ['1', '2']]));
        
        $action = Action\Edit()
            ->setFields(new new Field\Fields($field))
            ->setInput(new Input(['search' => ['name' => 'fo']]));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertStringContainsString(
            '<input name="name[]" type="checkbox" value="1">',
            $field->render()
        );
    }
    
    public function testProcessCreateEditRendersSearchInputUsesBaseWhere()
    {
        $repo = $this->createRepository();
        $repo->create(['name' => 'foo', 'type' => 'tag']);
        $repo->create(['name' => 'bar', 'type' => 'color']);
        
        $field = new Field\Options(name: 'name')
            ->repository($repo)
            ->baseWhere(['type' => 'tag'])
            ->toOption(function(object $item, ViewInterface $view, new Field\Options $options): new Field\Option {        
                return new new Field\Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('name'),
                );
            })
            ->setEntity(new Entity(['name' => ['1', '2']]));
        
        $field->processCreateEdit(
            action: Action\Edit(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<input name="name[]" type="checkbox" value="1" checked>',
            $field->render()
        );
        
        $this->assertStringNotContainsString(
            '<input name="name[]" type="checkbox" value="2" checked>',
            $field->render()
        );
    }
    
    public function testProcessSave()
    {
        $field = new Field\Options(name: 'color');
        $input = new Input(['color' => ['red']]);
        $field->processBeforeSave(field: $field, input: $input);
        $field->processSave(field: $field, input: $input);
        
        $this->assertSame(['color' => ['red']], $input->all());
    }
    
    public function testProcessSaveEmptyOptionIsRemoved()
    {
        $field = new Field\Options(name: 'color')->emptyOption(value: 'none');
        $input = new Input(['color' => ['none']]);
        $field->processBeforeSave(field: $field, input: $input);
        $field->processSave(field: $field, input: $input);
        
        $this->assertSame(['color' => []], $input->all());
    }
    
    public function testValidatePasses()
    {
        $repo = $this->createRepository();
        $repo->create(['name' => 'foo']);
        $repo->create(['name' => 'bar']);
        
        $field = new Field\Options(name: 'name')
            ->repository($repo)
            ->toOption(function(object $item, ViewInterface $view, new Field\Options $options): new Field\Option {        
                return new new Field\Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('name'),
                );
            })
            ->setEntity(new Entity(['name' => ['1', '2']]));
        
        $validation = Factory::createValidator()->validate(
            data: ['name' => ['1', '2']],
            rules: $field->getValidationRulesForAction('create'),
        );
        
        $this->assertTrue($validation->isValid());
    }
    
    public function testValidateFailsIfInvalidOptionUsingBaseWhere()
    {
        $repo = $this->createRepository();
        $repo->create(['name' => 'foo', 'type' => 'tag']);
        $repo->create(['name' => 'bar', 'type' => 'color']);
        
        $field = new Field\Options(name: 'name')
            ->repository($repo)
            ->baseWhere(['type' => 'tag'])
            ->toOption(function(object $item, ViewInterface $view, new Field\Options $options): new Field\Option {        
                return new new Field\Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('name'),
                );
            })
            ->setEntity(new Entity(['name' => ['1', '2']]));
        
        $rules = $field->getValidationRulesForAction('create');
        
        $validation = Factory::createValidator()->validate(
            data: ['name' => ['1', '2']],
            rules: $rules,
        );
        
        $this->assertFalse($validation->isValid());
    }
    
    public function testValidateFailsIfInvalidOption()
    {
        $repo = $this->createRepository();
        $repo->create(['name' => 'foo']);
        $repo->create(['name' => 'bar']);
        
        $field = new Field\Options(name: 'name')
            ->repository($repo)
            ->toOption(function(object $item, ViewInterface $view, new Field\Options $options): new Field\Option {        
                return new new Field\Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('name'),
                );
            })
            ->setEntity(new Entity(['name' => ['1', '2']]));
        
        $rules = $field->getValidationRulesForAction('create');
        
        $validation = Factory::createValidator()->validate(
            data: ['name' => '3'],
            rules: $rules,
        );
        
        $this->assertFalse($validation->isValid());
        
        $validation = Factory::createValidator()->validate(
            data: ['name' => ['3']],
            rules: $rules,
        );
        
        $this->assertFalse($validation->isValid());
    }
    
    public function testValidateRulesAreMerged()
    {
        $field = new Field\Options(name: 'color')->validate('required|minItems:2|maxItems:10');
        $rules = $field->getValidationRulesForAction('create');
        
        $this->assertSame('required|minItems:2|maxItems:10', $rules['color'][0] ?? null);
        $this->assertInstanceof(Rule\Passes::class, $rules['color'][1] ?? null);
    }
}