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

use Psr\Container\ContainerInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Test\Factory;
use Tobento\Service\Container\Container;
use Tobento\Service\View\ViewInterface;

/**
 * More is tested on Test\Feature\Field\Items::class
 */
class ItemsTest extends AbstractField
{
    public function getContainer(): ContainerInterface
    {
        $container = new Container();
        $container->set(ViewInterface::class, Factory::createView());
        return $container;
    }
    
    public function testDefaultInterfaceMethods()
    {
        $field = Field\Items::new(name: 'name');
        $this->assertInstanceof(Field\Items::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(Field\Items::new(name: 'name'));
        $this->renderTests(Field\Items::new(name: 'name'));
        $this->nameTests(Field\Items::class);
        $this->labelTests(Field\Items::class);
        $this->groupTests(Field\Items::class);
        $this->localeTests(Field\Items::new(name: 'name'));
        $this->storableTests(Field\Items::new(name: 'name'));
        $this->indexableTests(Field\Items::new(name: 'name'));
        $this->creatableTests(Field\Items::new(name: 'name'));
        $this->editableTests(Field\Items::new(name: 'name'));
        $this->entityTests(Field\Items::new(name: 'name'));
        $this->validateTests(Field\Items::class, withTranslatable: false);
        $this->requiredTextTests(Field\Items::class, withTranslatable: false);
        $this->optionalTextTests(Field\Items::class, withTranslatable: false);
        $this->infoTextTests(Field\Items::class);
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
        $this->processIndexTests(Field\Items::class, withTranslatable: false);
        //$this->processStoreTests(Field\Items::class, withTranslatable: false);
        //$this->processUpdateTests(Field\Items::class, withTranslatable: false);
        $this->processShowTests(Field\Items::class, withTranslatable: false);
    }
    
    public function testProcessIndex()
    {
        $field = Field\Items::new(name: 'name')
            ->fields(
                Field\Text::new('price_net', 'Price Net')
                    ->type('number')
                    ->attributes(['step' => 'any'])
                    ->validate('decimal'),
            );
        
        $action = Action\Index::new()
            ->setFields(new Field\Fields($field))
            ->setEntity(new Entity(['name' => [1 => ['price_net' => '5.00'], 2 => ['price_net' => '7.00']]]));
        
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $rendered = $action->fields()->get(name: $field->name())->render();
        
        $this->assertStringContainsString(
            json_encode([1 => ['price_net' => '5.00'], 2 => ['price_net' => '7.00']], JSON_PRETTY_PRINT),
            $rendered
        );
    }

    public function testProcessCreate()
    {
        $field = Field\Items::new(name: 'name')
            ->fields(
                Field\Text::new('price_net', 'Price Net')
                    ->type('number')
                    ->attributes(['step' => 'any'])
                    ->validate('decimal'),
            );
        
        $action = Action\Create::new()->setFields(new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $rendered = $action->fields()->get(name: $field->name())->render();
        
        $this->assertStringNotContainsString('<input step="any" name="name[1][price_net]"', $rendered);
        $this->assertStringContainsString('Add new item', $rendered);
    }
    
    public function testProcessCreateWithDefaultItems()
    {
        $field = Field\Items::new(name: 'name')
            ->fields(
                Field\Text::new('price_net', 'Price Net')
                    ->type('number')
                    ->attributes(['step' => 'any'])
                    ->validate('decimal'),
            )
            ->defaultItems(num: 1);
        
        $action = Action\Create::new()->setFields(new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $rendered = $action->fields()->get(name: $field->name())->render();
        
        $this->assertStringContainsString(
            '<input step="any" data-index="1" name="name[1][price_net]" id="name_1_price_net" type="number" value>',
            $rendered
        );
    }
    
    public function testProcessEdit()
    {
        $field = Field\Items::new(name: 'name')
            ->fields(
                Field\Text::new('price_net', 'Price Net')
                    ->type('number')
                    ->attributes(['step' => 'any'])
                    ->validate('decimal'),
            )
            ->defaultItems(num: 1);
        
        $action = Action\Create::new()
            ->setFields(new Field\Fields($field))
            ->setEntity(new Entity(['name' => [1 => ['price_net' => '5.00'], 2 => ['price_net' => '7.00']]]));
        
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $rendered = $action->fields()->get(name: $field->name())->render();
        
        $this->assertStringContainsString(
            '<input step="any" data-index="1" name="name[1][price_net]" id="name_1_price_net" type="number" value="5.00">',
            $rendered
        );
        $this->assertStringContainsString(
            '<input step="any" data-index="2" name="name[2][price_net]" id="name_2_price_net" type="number" value="7.00">',
            $rendered
        );
    }
    
    public function testProcessStore()
    {
        $field = Field\Items::new(name: 'name')
            ->fields(
                Field\Text::new('price_net', 'Price Net')
                    ->type('number')
                    ->attributes(['step' => 'any'])
                    ->validate('decimal'),
            )
            ->defaultItems(num: 1);
        
        $action = Action\Create::new()
            ->setFields(new Field\Fields($field))
            ->setEntity(new Entity(['name' => [1 => ['price_net' => '5.00'], 2 => ['price_net' => '7.00']]]));
        
        $actionProcessor = Factory::createActionProcessor();
        $actionProcessor->processFields(action: $action);
        $rendered = $action->fields()->get(name: $field->name())->render();
        
        $this->assertStringContainsString(
            '<input step="any" data-index="1" name="name[1][price_net]" id="name_1_price_net" type="number" value="5.00">',
            $rendered
        );
        $this->assertStringContainsString(
            '<input step="any" data-index="2" name="name[2][price_net]" id="name_2_price_net" type="number" value="7.00">',
            $rendered
        );
    }
}