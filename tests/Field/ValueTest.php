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

use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Input\Input;

class ValueTest extends AbstractField
{
    public function testDefaultInterfaceMethods()
    {
        $field = Field\Value::new(name: 'name');
        $this->assertInstanceof(Field\Value::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(Field\Value::new(name: 'name'));
        $this->renderTests(Field\Value::new(name: 'name'));
        $this->nameTests(Field\Value::class);
        $this->groupTests(Field\Value::class);
        $this->translatableTests(Field\Value::new(name: 'name'));
        $this->localeTests(Field\Value::new(name: 'name'));
        $this->storableTests(Field\Value::new(name: 'name'));
        $this->assertFalse(Field\Value::new(name: 'name')->isIndexable());
        $this->creatableTests(Field\Value::new(name: 'name'));
        $this->editableTests(Field\Value::new(name: 'name'));
        $this->readonlyTests(Field\Value::new(name: 'name'));
        $this->disabledTests(Field\Value::new(name: 'name'));
        $this->entityTests(Field\Value::new(name: 'name'));
        $this->validateTests(Field\Value::class);
        $this->requiredTextTests(Field\Value::class);
        $this->optionalTextTests(Field\Value::class);
        $this->infoTextTests(Field\Value::class);
    }
    
    public function testActionProcesses()
    {
        $this->processIndexTests(Field\Value::class);
        $this->processStoreTests(Field\Value::class);
        $this->processUpdateTests(Field\Value::class);
        $this->processShowTests(Field\Value::class);
    }
    
    public function testProcessSaveMethodAddsValue()
    {
        $input = new Input([]);
        $field = Field\Value::new(name: 'name')->value('foo');
        $field->processSave(field: $field, input: $input);
        $this->assertSame(['name' => 'foo'], $input->all());
    }
    
    public function testProcessSaveMethodWihtoutValue()
    {
        $input = new Input([]);
        $field = Field\Value::new(name: 'name');
        $field->processSave(field: $field, input: $input);
        $this->assertSame(['name' => null], $input->all());
    }
}