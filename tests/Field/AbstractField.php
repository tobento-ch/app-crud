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

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Test\Factory;

abstract class AbstractField extends TestCase
{
    public function processTests(FieldInterface $field)
    {
        $field->process('create', fn() => 'create');
        $field->process('foo|bar', fn() => 'foo|bar');
        
        $this->assertNull($field->getProcessor(action: 'baz'));
        $this->assertTrue(is_callable($field->getProcessor(action: 'create')));
        $this->assertTrue(is_callable($field->getProcessor(action: 'foo')));
        $this->assertTrue(is_callable($field->getProcessor(action: 'bar')));
    }
    
    public function renderTests(FieldInterface $field)
    {
        $this->assertSame('', $field->render());
        $this->assertSame('lorem', $field->html('lorem')->render());
    }

    public function renameTests(string $field)
    {
        $this->assertSame('new', $field::new(name: 'name')->rename('new')->name());
    }
    
    public function nameTests(string $field)
    {
        $this->assertSame('name', $field::new(name: 'name')->name());
    }
    
    public function labelTests(string $field)
    {
        $this->assertSame('Name', $field::new(name: 'name')->label());
        $this->assertSame('NAME', $field::new(name: 'name', label: 'NAME')->label());
    }
    
    public function groupTests(string $field)
    {
        $field = $field::new(name: 'name')->group('Group');
        $this->assertSame('Group', $field->groupName());
        $this->assertSame('group', $field->groupId());
        
        $field = $field::new(name: 'name')->group('Group Name');
        $this->assertSame('Group Name', $field->groupName());
        $this->assertSame('group-name', $field->groupId());
    }
    
    public function parentTests(string $field)
    {
        $field = $field::new(name: 'name');
        $this->assertSame(null, $field->parentField());
        
        $field = $field::new(name: 'name')->parent('field');
        $this->assertSame('field', $field->parentField());
    }
    
    public function translatableTests(FieldInterface $field)
    {
        $this->assertFalse($field->isTranslatable());
        $this->assertFalse($field->translatable(false)->isTranslatable());
        $this->assertTrue($field->translatable()->isTranslatable());
        $this->assertTrue($field->translatable(true)->isTranslatable());
    }
    
    public function localeTests(FieldInterface $field)
    {
        $this->assertSame('en', $field->locale());
        $this->assertSame(['en' => 'EN'], $field->locales());
        
        $this->assertSame('de', $field->setLocale('de')->locale());
        $this->assertSame(['de' => 'DE'], $field->setLocales(['de' => 'DE'])->locales());
    }
    
    public function storableTests(FieldInterface $field)
    {
        $this->assertTrue($field->isStorable());
        $this->assertFalse($field->storable(false)->isStorable());
        $this->assertTrue($field->storable()->isStorable());
        $this->assertTrue($field->storable(true)->isStorable());
    }
    
    public function indexableTests(FieldInterface $field)
    {
        $this->assertTrue($field->isIndexable());
        $this->assertFalse($field->indexable(false)->isIndexable());
        $this->assertTrue($field->indexable()->isIndexable());
        $this->assertTrue($field->indexable(true)->isIndexable());
    }
    
    public function creatableTests(FieldInterface $field)
    {
        $this->assertTrue($field->isCreatable());
        $this->assertFalse($field->creatable(false)->isCreatable());
        $this->assertTrue($field->creatable()->isCreatable());
        $this->assertTrue($field->creatable(true)->isCreatable());
    }
    
    public function editableTests(FieldInterface $field)
    {
        $this->assertTrue($field->isEditable());
        $this->assertFalse($field->editable(false)->isEditable());
        $this->assertTrue($field->editable()->isEditable());
        $this->assertTrue($field->editable(true)->isEditable());
    }
    
    public function readonlyTests(FieldInterface $field)
    {
        $this->assertFalse($field->isReadonly());
        $this->assertTrue($field->readonly(true)->isReadonly());
        $this->assertTrue($field->readonly()->isReadonly());
        $this->assertFalse($field->readonly(false)->isReadonly());
        
        // check if readonly attribute are set
        $this->assertTrue(in_array('readonly', $field->readonly(true)->getAttributes()));
        $this->assertFalse(in_array('readonly', $field->readonly(false)->getAttributes()));
        
        // check if storable
        $this->assertFalse($field->readonly(true)->isStorable());
        $this->assertTrue($field->readonly(false)->isStorable());
        
        // test with callable
        $field->readonly(function(ActionInterface $action, FieldInterface $field) {
            return true;
        });
        $action = Action\Edit::new()->setFields(new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertTrue($field->isReadonly());
    }
    
    public function disabledTests(FieldInterface $field)
    {
        $this->assertFalse($field->isDisabled());
        $this->assertTrue($field->disabled(true)->isDisabled());
        $this->assertTrue($field->disabled()->isDisabled());
        $this->assertFalse($field->disabled(false)->isDisabled());
        
        // check if disabled attribute are set
        $this->assertTrue(in_array('disabled', $field->disabled(true)->getAttributes()));
        $this->assertFalse(in_array('disabled', $field->disabled(false)->getAttributes()));
        
        // check if storable
        $this->assertFalse($field->disabled(true)->isStorable());
        $this->assertTrue($field->disabled(false)->isStorable());
        
        // test with callable
        $field->disabled(function(ActionInterface $action, FieldInterface $field) {
            return true;
        });
        $action = Action\Edit::new()->setFields(new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertTrue($field->isDisabled());
    }
    
    public function entityTests(FieldInterface $field)
    {
        $this->assertInstanceof(EntityInterface::class, $field->entity());
        
        $entity = new Entity();
        $this->assertSame($entity, $field->setEntity($entity)->entity());
    }
    
    public function validateTests(string $fieldName, mixed $defaultValidate = null, bool $withTranslatable = true)
    {
        $field = $fieldName::new('name');
        if (!is_null($defaultValidate)) {
            $this->assertSame([$defaultValidate], $field->getValidate());
        } else {
            $this->assertSame(null, $field->getValidate());
        }
        $this->assertSame(['required|string'], $field->validate('required|string')->getValidate());
        $this->assertSame(['store' => 'required|string'], $field->validate(store: 'required|string')->getValidate());
        
        // action rules:
        $field = $fieldName::new('name');
        if (!is_null($defaultValidate)) {
            $this->assertSame(['name' => $defaultValidate], $field->getValidationRulesForAction(action: 'undefined'));
        } else {
            $this->assertSame(null, $field->getValidationRulesForAction(action: 'undefined'));
        }

        $this->assertSame(
            ['name' => 'required|string'],
            $field->validate('required|string')->getValidationRulesForAction(action: 'store')
        );
        
        $this->assertSame(
            ['name' => 'required|string'],
            $field->validate(default: 'required|string')->getValidationRulesForAction(action: 'store')
        );        
        
        $this->assertSame(
            ['name' => 'required|string'],
            $field->validate(store: 'required|string')->getValidationRulesForAction(action: 'store')
        );
        
        if (!$withTranslatable) {
            return;
        }
        
        // translatable:
        $field = $fieldName::new('name')->translatable();
        
        $this->assertSame(
            ['name.en' => 'required|string'],
            $field->validate(update: 'required|string')->getValidationRulesForAction(action: 'update')
        );
        
        $field = $fieldName::new('name')->translatable()->setLocales(['en' => 'EN', 'de' => 'DE']);
        
        $this->assertSame(
            ['name.en' => 'required|string', 'name.de' => 'required|string'],
            $field->validate(update: 'required|string')->getValidationRulesForAction(action: 'update')
        );        
    }
    
    public function requiredTextTests(string $fieldName, bool $withTranslatable = true)
    {
        $field = $fieldName::new('name');
        $this->assertSame('', $field->getRequiredText(action: 'edit'));
        
        $field = $fieldName::new('name')->requiredText('required');
        $this->assertSame('required', $field->getRequiredText(action: 'create'));
        $this->assertSame('required', $field->getRequiredText(action: 'edit'));
        $this->assertSame('', $field->getRequiredText(action: 'foo'));
        
        // Test automatically adds required if rule as required.
        $field = $fieldName::new('name')->validate('required|string');
        $this->assertSame('required', $field->getRequiredText(action: 'create'));
        $this->assertSame('required', $field->getRequiredText(action: 'edit'));
        
        $field = $fieldName::new('name')->validate(store: 'required|string');
        $this->assertSame('required', $field->getRequiredText(action: 'create'));
        $this->assertSame('', $field->getRequiredText(action: 'edit'));
        
        if (!$withTranslatable) {
            return;
        }
        
        // when translatable:
        $field = $fieldName::new('name')->translatable()->validate('required|string');
        $this->assertSame('required', $field->getRequiredText(action: 'create'));
    }
    
    public function optionalTextTests(string $fieldName, bool $withTranslatable = true)
    {
        $field = $fieldName::new('name');
        $this->assertSame('optional', $field->getOptionalText(action: 'edit'));
        
        $field = $fieldName::new('name')->optionalText('optional');
        $this->assertSame('optional', $field->getOptionalText(action: 'create'));
        $this->assertSame('optional', $field->getOptionalText(action: 'edit'));
        
        // Test should not automaically add optional if rule has required.
        $field = $fieldName::new('name')->validate('required|string');
        $this->assertSame('', $field->getOptionalText(action: 'create'));
        $this->assertSame('', $field->getOptionalText(action: 'edit'));
        
        $field = $fieldName::new('name')->validate(store: 'required|string');
        $this->assertSame('', $field->getOptionalText(action: 'create'));
        $this->assertSame('optional', $field->getOptionalText(action: 'edit'));
        
        if (!$withTranslatable) {
            return;
        }
        
        // when translatable:
        $field = $fieldName::new('name')->translatable()->validate('required|string');
        $this->assertSame('', $field->getOptionalText(action: 'create'));
    }
    
    public function infoTextTests(string $fieldName)
    {
        $field = $fieldName::new('name');
        $this->assertSame('', $field->getInfoText(action: 'edit'));
        
        $field = $fieldName::new('name')->infoText('lorem');
        $this->assertSame('lorem', $field->getInfoText(action: 'create'));
        $this->assertSame('lorem', $field->getInfoText(action: 'edit'));
    }

    public function processIndexTests(string $fieldName, bool $withTranslatable = true)
    {
        $field = $fieldName::new('name');
        $field->processIndex(field: $field);
        $this->assertSame('', $field->render());
        
        $field = $fieldName::new('name')->setEntity(new Entity(['name' => 'foo']));
        $field->processIndex(field: $field);
        $this->assertSame('foo', $field->render());
        
        $field = $fieldName::new('name')->setEntity(new Entity(['name' => '<p>foo</p>']));
        $field->processIndex(field: $field);
        $this->assertSame('&lt;p&gt;foo&lt;/p&gt;', $field->render());
        
        if (!$withTranslatable) {
            return;
        }
        
        // translatable:
        $field = $fieldName::new('name')->setEntity(new Entity(['name' => ['en' => 'foo']]))->translatable();
        $field->processIndex(field: $field);
        $this->assertSame('foo', $field->render());
        
        $field = $fieldName::new('name')->setEntity(new Entity(['name' => ['en' => 'foo']]))->translatable()->setLocale('de');
        $field->processIndex(field: $field);
        $this->assertSame('', $field->render());
        
        $field = $fieldName::new('name')->setEntity(new Entity(['name' => ['de' => 'foo']]))->translatable()->setLocale('de');
        $field->processIndex(field: $field);
        $this->assertSame('foo', $field->render());
    }
    
    public function processStoreTests(string $fieldName, bool $withTranslatable = true)
    {
        $field = $fieldName::new('name');
        $input = new Input();
        $field->processStore(field: $field, input: $input);
        $this->assertSame(null, $input->get('name'));
        
        $field = $fieldName::new('name');
        $input = new Input(['name' => 'foo']);
        $field->processStore(field: $field, input: $input);
        $this->assertSame('foo', $input->get('name'));
        
        if (!$withTranslatable) {
            return;
        }
        
        // translatable:
        $field = $fieldName::new('name')->translatable();
        $input = new Input(['name' => ['en' => 'foo', 'de' => 'bar']]);
        $field->processStore(field: $field, input: $input);
        $this->assertSame(['en' => 'foo'], $input->get('name'));
        
        $field = $fieldName::new('name')->translatable()->setLocales(['en' => 'EN', 'de' => 'DE']);
        $input = new Input(['name' => ['en' => 'foo', 'de' => 'bar']]);
        $field->processStore(field: $field, input: $input);
        $this->assertSame(['en' => 'foo', 'de' => 'bar'], $input->get('name'));
        
        // maps to default locales if not array input:
        $field = $fieldName::new('name')->translatable();
        $input = new Input(['name' => 'foo']);
        $field->processStore(field: $field, input: $input);
        $this->assertSame(['en' => 'foo'], $input->get('name'));
    }
    
    public function processUpdateTests(string $fieldName, bool $withTranslatable = true)
    {
        $field = $fieldName::new('name');
        $input = new Input();
        $field->processUpdate(field: $field, input: $input);
        $this->assertSame(null, $input->get('name'));
        
        $field = $fieldName::new('name');
        $input = new Input(['name' => 'foo']);
        $field->processUpdate(field: $field, input: $input);
        $this->assertSame('foo', $input->get('name'));
        
        if (!$withTranslatable) {
            return;
        }
        
        // translatable:
        $field = $fieldName::new('name')->translatable();
        $input = new Input(['name' => ['en' => 'foo', 'de' => 'bar']]);
        $field->processUpdate(field: $field, input: $input);
        $this->assertSame(['en' => 'foo'], $input->get('name'));
        
        $field = $fieldName::new('name')->translatable()->setLocales(['en' => 'EN', 'de' => 'DE']);
        $input = new Input(['name' => ['en' => 'foo', 'de' => 'bar']]);
        $field->processUpdate(field: $field, input: $input);
        $this->assertSame(['en' => 'foo', 'de' => 'bar'], $input->get('name'));
        
        // maps to default locales if not array input:
        $field = $fieldName::new('name')->translatable();
        $input = new Input(['name' => 'foo']);
        $field->processUpdate(field: $field, input: $input);
        $this->assertSame(['en' => 'foo'], $input->get('name'));
    }
    
    public function processShowTests(string $fieldName, bool $withTranslatable = true)
    {
        $field = $fieldName::new('name')->setEntity(new Entity([]));
        $field->processShow(field: $field);
        $this->assertSame('', $field->render());
        
        $field = $fieldName::new('name')->setEntity(new Entity(['name' => 'foo']));
        $field->processShow(field: $field);
        $this->assertSame('foo', $field->render());
        
        $field = $fieldName::new('name')->setEntity(new Entity(['name' => '<p>foo</p>']));
        $field->processShow(field: $field);
        $this->assertSame('&lt;p&gt;foo&lt;/p&gt;', $field->render());
        
        if (!$withTranslatable) {
            return;
        }
        
        // translatable:
        $field = $fieldName::new('name')->setEntity(new Entity(['name' => ['en' => 'foo']]))->translatable();
        $field->processShow(field: $field);
        $this->assertSame('foo', $field->render());
        
        $field = $fieldName::new('name')->setEntity(new Entity(['name' => ['en' => 'foo']]))->translatable()->setLocale('de');
        $field->processShow(field: $field);
        $this->assertSame('', $field->render());
        
        $field = $fieldName::new('name')->setEntity(new Entity(['name' => ['de' => 'foo']]))->translatable()->setLocale('de');
        $field->processShow(field: $field);
        $this->assertSame('foo', $field->render());
    }
}