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
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Test\Factory;

class GroupTest extends AbstractField
{
    public function testDefaultInterfaceMethods()
    {
        $field = new Field\Group(name: 'name');
        $this->assertInstanceof(Field\Group::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(new Field\Group(name: 'name'));
        $this->renderTests(new Field\Group(name: 'name'));
        $this->nameTests(Field\Group::class);
        $this->labelTests(Field\Group::class);
        $this->groupTests(Field\Group::class);
        $this->translatableTests(new Field\Group(name: 'name'));
        $this->localeTests(new Field\Group(name: 'name'));
        $this->storableTests(new Field\Items(name: 'name'));
        $this->indexableTests(new Field\Items(name: 'name'));
        $this->creatableTests(new Field\Group(name: 'name'));
        $this->editableTests(new Field\Group(name: 'name'));
        $this->entityTests(new Field\Group(name: 'name'));
        $this->validateTests(Field\Group::class);
        $this->requiredTextTests(Field\Group::class);
        $this->optionalTextTests(Field\Group::class);
        $this->infoTextTests(Field\Group::class);
    }
    
    public function indexableTests(FieldInterface $field)
    {
        $this->assertTrue($field->isIndexable());
        $this->assertFalse($field->indexable(false)->isIndexable());
        $this->assertTrue($field->indexable()->isIndexable());
        $this->assertTrue($field->indexable(true)->isIndexable());
    }
    
    public function storableTests(FieldInterface $field)
    {
        $this->assertFalse($field->isStorable());
        $this->assertFalse($field->storable(false)->isStorable());
        $this->assertTrue($field->storable()->isStorable());
        $this->assertTrue($field->storable(true)->isStorable());
    }
    
    public function testActionProcesses()
    {
        $this->processIndexTests(Field\Group::class);
        $this->processStoreTests(Field\Group::class);
        $this->processUpdateTests(Field\Group::class);
        $this->processShowTests(Field\Group::class);
    }
    
    public function testProcessCreateEdit()
    {
        $field = new Field\Group(name: 'name')
            ->fields(
                new Field\Text('title', 'Title'),
            )
            ->displayAsCard();
        
        $action = new Action\Create()
            ->setFields(new Field\Fields($field))
            ->setEntity(new Entity(['title' => 'Foo']));
        
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $rendered = $action->fields()->get(name: $field->name())->render();
        
        $this->assertStringContainsString('<div data-field="name">', $rendered);
        $this->assertStringContainsString('<input name="title" id="title" type="text" value="Foo">', $rendered);
    }

    public function testProcessCreateEditNotRendersFieldsIfNotDisplayAsCard()
    {
        $field = new Field\Group(name: 'name')
            ->fields(
                new Field\Text('title', 'Title'),
            );
        
        $action = new Action\Create()->setFields(new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $rendered = $action->fields()->get(name: $field->name())->render();
        
        $this->assertStringContainsString('', $rendered);
    }

    public function testPrependsGroupName()
    {
        $field = new Field\Group(name: 'name')
            ->fields(
                new Field\Text('title', 'Title'),
            )
            ->prependGroupName();
        
        $text = $field->getFields(new Action\Create())->get('title');
        
        $this->assertSame('name.title', $text->name());
    }
    
    public function testRenamesFields()
    {
        $field = new Field\Group(name: 'name')
            ->fields(
                new Field\Text('title', 'Title'),
            )
            ->renameFields(['title' => 'meta.title']);
        
        $text = $field->getFields(new Action\Create())->get('title');
        
        $this->assertSame('meta.title', $text->name());
    }
    
    public function testRemovesFields()
    {
        $field = new Field\Group(name: 'name')
            ->fields(
                new Field\Text('title', 'Title'),
            )
            ->removeField('title');

        $this->assertSame(0, $field->getFields(new Action\Create())->count());
    }
    
    public function testModifiesField()
    {
        $field = new Field\Group(name: 'name')
            ->fields(
                new Field\Text('title', 'Title'),
            )
            ->modifyField(name: 'title', modifier: function(FieldInterface $field): void {
                $field->translatable(true);
            });
        
        $text = $field->getFields(new Action\Create())->get('title');
        
        $this->assertTrue($text->isTranslatable());
    }
    
    public function testModifiesFields()
    {
        $field = new Field\Group(name: 'name')
            ->fields(
                new Field\Text('title', 'Title'),
            )
            ->modifyFields(modifier: function(FieldsInterface $fields): FieldsInterface {
                return new Field\Fields(
                    new Field\Text('desc', 'Desc'),
                    ...$fields->all(),
                );
            });
        
        $this->assertSame(2, $field->getFields(new Action\Create())->count());
    }
}