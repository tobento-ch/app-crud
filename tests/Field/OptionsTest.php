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
use Tobento\App\Crud\Entity\Entities;
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
                new Column\Id(),
                new Column\Text('name'),
                new Column\Text('type'),
            ],
        );
    }
    
    public function testDefaultInterfaceMethods()
    {
        $field = new Field\Options(name: 'name');
        $this->assertInstanceof(Field\Options::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(new Field\Options(name: 'name'));
        $this->renderTests(new Field\Options(name: 'name'));
        $this->nameTests(Field\Options::class);
        $this->labelTests(Field\Options::class);
        $this->groupTests(Field\Options::class);
        $this->localeTests(new Field\Options(name: 'name'));
        $this->storableTests(new Field\Options(name: 'name'));
        $this->indexableTests(new Field\Options(name: 'name'));
        $this->creatableTests(new Field\Options(name: 'name'));
        $this->editableTests(new Field\Options(name: 'name'));
        $this->readonlyTests(new Field\Text(name: 'name'));
        $this->disabledTests(new Field\Text(name: 'name'));
        $this->entityTests(new Field\Options(name: 'name'));
        $this->requiredTextTests(Field\Options::class, withTranslatable: false);
        $this->optionalTextTests(Field\Options::class, withTranslatable: false);
        $this->infoTextTests(Field\Options::class);
    }
    
    public function testActionProcesses()
    {
        $this->processStoreTests(Field\Options::class, withTranslatable: false);
        $this->processUpdateTests(Field\Options::class, withTranslatable: false);
        //$this->processShowTests(Field\Options::class, withTranslatable: false);
    }
    
    public function testProcessIndex()
    {
        $repo = $this->createRepository();
        $repo->create(['name' => 'red']);
        $repo->create(['name' => 'blue']);

        $field = new Field\Options(name: 'color')
            ->repository($repo)
            ->toOption(function(object $item, ViewInterface $view, Field\Options $options): Field\Option {        
                return new Field\Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('name'),
                );
            })
            ->setEntity(new Entity(['color' => ['1', '2']]));
        
        $action = new Action\Index()->setEntities(new Entities([
            new Entity(['id' => 1, 'color' => ['1', '2']]),
            new Entity(['id' => 2, 'color' => []]),
        ]));
        
        $field->processIndexAction(action: $action, field: $field, view: Factory::createView());
        
        $rendered = $field->render();
        $this->assertStringContainsString('red', $rendered);
        $this->assertStringContainsString('blue', $rendered);
    }
    
    public function testProcessShow()
    {
        $repo = $this->createRepository();
        $repo->create(['name' => 'red']);
        $repo->create(['name' => 'blue']);
        
        $field = new Field\Options(name: 'color')
            ->repository($repo)
            ->toOption(function(object $item, ViewInterface $view, Field\Options $options): Field\Option {        
                return new Field\Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('name'),
                );
            })
            ->setEntity(new Entity(['color' => ['1', '2']]));

        $field->processShow(field: $field, view: Factory::createView());

        $rendered = $field->render();
        $this->assertStringContainsString('red', $rendered);
        $this->assertStringContainsString('blue', $rendered);        
    }
    
    public function testProcessCreateEditRendersSearchInput()
    {
        $field = new Field\Options(name: 'foo.bar')
            ->repository($this->createRepository());
        
        $field->processCreateEdit(
            action: new Action\Edit(),
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
            action: new Action\Edit(),
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
            action: new Action\Edit(),
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
            ->toOption(function(object $item, ViewInterface $view, Field\Options $options): Field\Option {        
                return new Field\Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('name'),
                );
            })
            ->setEntity(new Entity(['name' => ['1', '2']]));
        
        $field->processCreateEdit(
            action: new Action\Edit(),
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
            ->toOption(function(object $item, ViewInterface $view, Field\Options $options): Field\Option {        
                return new Field\Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('name'),
                );
            })
            ->selected(value: ['1'], action: 'edit');
        
        $action = new Action\Edit()->setFields(new Field\Fields($field));
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
            ->toOption(function(object $item, ViewInterface $view, Field\Options $options): Field\Option {        
                return new Field\Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('name'),
                );
            })
            ->setEntity(new Entity(['name' => ['1', '2']]));
        
        $action = new Action\Edit()
            ->setFields(new Field\Fields($field))
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
            ->toOption(function(object $item, ViewInterface $view, Field\Options $options): Field\Option {        
                return new Field\Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('name'),
                );
            })
            ->setEntity(new Entity(['name' => ['1', '2']]));
        
        $field->processCreateEdit(
            action: new Action\Edit(),
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
    
    public function testProcessCreateEditWithHidden()
    {
        $field = new Field\Options(name: 'foo.bar')
            ->hidden()
            ->repository($this->createRepository());
        
        $field->processCreateEdit(
            action: new Action\Edit(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertSame('', $field->render());
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
            ->toOption(function(object $item, ViewInterface $view, Field\Options $options): Field\Option {        
                return new Field\Option(
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
            ->toOption(function(object $item, ViewInterface $view, Field\Options $options): Field\Option {        
                return new Field\Option(
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
            ->toOption(function(object $item, ViewInterface $view, Field\Options $options): Field\Option {        
                return new Field\Option(
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
    
    public function testLiveFeature()
    {
        $repo = $this->createRepository();
        $repo->create(['name' => 'foo', 'type' => 'tag']);
        
        $field = new Field\Options(name: 'name')
            ->repository($repo)
            ->live()
            ->toOption(function(object $item, ViewInterface $view, Field\Options $options): Field\Option {        
                return new Field\Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('name'),
                );
            })
            ->setEntity(new Entity(['name' => ['1']]));
        
        $field->processCreateEdit(
            action: new Action\Edit(),
            field: $field,
            view: Factory::createView(),
        );
        
        $this->assertStringContainsString(
            '<input name="name[]" type="checkbox" value="1" checked data-live=\'{&quot;fields&quot;:[],&quot;selectors&quot;:[],&quot;blur&quot;:false,&quot;debounce&quot;:0}\'>',
            $field->render()
        );
        
        $this->assertInstanceof(\Tobento\App\Crud\Field\LiveAwareInterface::class, $field);
    }    
}