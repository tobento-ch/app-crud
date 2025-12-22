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

namespace Tobento\App\Crud\Test\Filter;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Filter\Columns;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Action\Index;
use Tobento\App\Crud\Test\Factory;

class ColumnsTest extends TestCase
{
    public function testDefaultInterfaceMethods()
    {
        $filter = new Columns();
        
        $this->assertInstanceof(FilterInterface::class, $filter);
        $this->assertSame('columns', $filter->name());
        $this->assertSame('', $filter->fieldName());
        $this->assertSame('header', $filter->getGroup());
        $this->assertSame('footer', $filter->group('footer')->getGroup());
        $this->assertTrue($filter->isOpen());
        $this->assertFalse($filter->open(false)->isOpen());
        $this->assertTrue($filter->open(true)->isOpen());
        $this->assertSame(['columns' => []], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
        $this->assertSame([], $filter->getOrderByParameters());
        $this->assertSame([], $filter->getLimitParameter());
        $this->assertSame([], $filter->getBeforeCallables());
        $this->assertSame([], $filter->getAfterCallables());
        $this->assertFalse($filter->isActive());
    }
    
    public function testApplyFields()
    {
        $filter = new Columns();
        
        $filter->apply(
            input: new Input(['columns' => ['id', 'sku']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
                new Field\Text(name: 'title'),
            )),
        );
        
        $this->assertSame(['columns' => ['id', 'sku']], $filter->getAppliedParameters());
        $this->assertSame(['id', 'sku'], $filter->columns());
        $this->assertTrue($filter->isActive());
    }
    
    public function testApplyUsesDefaultFields()
    {
        $filter = new Columns()->default('title', 'sku');
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
                new Field\Text(name: 'title'),
            )),
        );
        
        $this->assertSame(['columns' => ['title', 'sku']], $filter->getAppliedParameters());
        $this->assertSame(['title', 'sku'], $filter->columns());
        $this->assertTrue($filter->isActive());
    }
    
    public function testApplyReordersFields()
    {
        $filter = new Columns()->reorder('title', 'sku');
        
        $action = new Index()->setFields(new Fields(
            new Field\Text(name: 'id'),
            new Field\Text(name: 'sku'),
            new Field\Text(name: 'title'),
        ));
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: $action,
        );
        
        $this->assertSame(['columns' => ['title', 'sku', 'id', 'actions']], $filter->getAppliedParameters());
        $this->assertSame(['title', 'sku', 'id', 'actions'], $filter->columns());
        $this->assertTrue($filter->isActive());
        $this->assertSame(['title', 'sku', 'id'], $action->fields()->getNames());
    }
    
    public function testApplyUsesFieldsOnly()
    {
        $filter = new Columns()->only('title', 'sku');
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
                new Field\Text(name: 'title'),
            )),
        );
        
        $this->assertSame(['columns' => ['title', 'sku']], $filter->getAppliedParameters());
        $this->assertSame(['title', 'sku'], $filter->columns());
        $this->assertTrue($filter->isActive());
    }
    
    public function testApplyUsesFieldsExcept()
    {
        $filter = new Columns()->except('title', 'sku');
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
                new Field\Text(name: 'title'),
            )),
        );
        
        $this->assertSame(['columns' => ['id', 'actions']], $filter->getAppliedParameters());
        $this->assertSame(['id', 'actions'], $filter->columns());
        $this->assertTrue($filter->isActive());
    }
    
    public function testApplyReordersFieldsFromInput()
    {
        $filter = new Columns()->reorder('title', 'sku');
        
        $action = new Index()->setFields(new Fields(
            new Field\Text(name: 'id'),
            new Field\Text(name: 'sku'),
            new Field\Text(name: 'title'),
        ));
        
        $filter->apply(
            input: new Input(['columns' => ['sku', 'id']]),
            filters: new Filters(),
            action: $action,
        );
        
        $this->assertSame(['columns' => ['sku', 'id']], $filter->getAppliedParameters());
        $this->assertSame(['sku', 'id'], $filter->columns());
        $this->assertTrue($filter->isActive());
        $this->assertSame(['sku', 'id', 'title'], $action->fields()->getNames());
    }
    
    public function testApplyWithEmptyDataAppliesDefaultFields()
    {
        $filter = new Columns();
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['columns' => ['sku', 'actions']], $filter->getAppliedParameters());
        $this->assertSame(['sku', 'actions'], $filter->columns());
    }
    
    public function testApplyWithEmptyDataAppliesDefaultFieldsLimits()
    {
        $filter = new Columns();
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
                new Field\Text(name: 'title'),
                new Field\Text(name: 'desc'),
                new Field\Text(name: 'intro'),
                new Field\Text(name: 'created_at'),
                new Field\Text(name: 'actions'),
            )),
        );
        
        $this->assertSame(6, count($filter->getAppliedParameters()['columns'] ?? []));
        $this->assertSame(6, count($filter->columns()));
    }
    
    public function testApplyWithNoneDataAppliesDefaultFields()
    {
        $filter = new Columns();
        
        $filter->apply(
            input: new Input(['columns' => ['_none']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['columns' => ['sku', 'actions']], $filter->getAppliedParameters());
        $this->assertSame(['sku', 'actions'], $filter->columns());
    }
    
    public function testApplyWithInvalidDataTypeAppliesDefaultFields()
    {
        $filter = new Columns();
        
        $filter->apply(
            input: new Input(['columns' => 'invalid']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['columns' => ['sku', 'actions']], $filter->getAppliedParameters());
        $this->assertSame(['sku', 'actions'], $filter->columns());
    }
    
    public function testApplyIgnoresInvalidFields()
    {
        $filter = new Columns();
        
        $filter->apply(
            input: new Input(['columns' => ['id', 'invalid', ['invalid']]]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['columns' => ['id']], $filter->getAppliedParameters());
        $this->assertSame(['id'], $filter->columns());
    }
    
    public function testApplyIgnoresNotIndexableFields()
    {
        $filter = new Columns();
        
        $filter->apply(
            input: new Input(['columns' => ['id', 'sku']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku')->indexable(false),
            )),
        );
        
        $this->assertSame(['columns' => ['id']], $filter->getAppliedParameters());
        $this->assertSame(['id'], $filter->columns());
    }
    
    public function testApplyClearsColumns()
    {
        $filter = new Columns();
        
        $filter->apply(
            input: new Input(['columns' => ['id', 'sku']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
                new Field\Text(name: 'title'),
            )),
        );
        
        $filter->apply(
            input: new Input(['columns' => ['sku']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
                new Field\Text(name: 'title'),
            )),
        );
        
        $this->assertSame(['columns' => ['sku']], $filter->getAppliedParameters());
        $this->assertSame(['sku'], $filter->columns());
    }
    
    public function testRender()
    {
        $filter = new Columns()->group('header')->label('LABEL')->description('DESC');
        
        $filter->apply(
            input: new Input(['columns' => ['id']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
            )),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('LABEL', $rendered);
        $this->assertStringContainsString('DESC', $rendered);
        $this->assertStringContainsString('name="filter[columns][]" type="checkbox" value="id"', $rendered);
        $this->assertStringContainsString('name="filter[columns][]" type="checkbox" value="sku"', $rendered);
        $this->assertStringContainsString('name="filter[columns][]" type="hidden" value="_none"', $rendered);
        
        $this->assertStringContainsString('class="crud-drag', $rendered);
        
        // unique id with group header:
        $this->assertStringContainsString('id="filter_columns_header_sku"', $rendered);
        $this->assertStringContainsString('label for="filter_columns_header_sku"', $rendered);
    }
    
    public function testRenderIgnoresNotIndexableFields()
    {
        $filter = new Columns()->group('header')->label('LABEL')->description('DESC');
        
        $filter->apply(
            input: new Input(['columns' => ['id']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku')->indexable(false),
            )),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringNotContainsString('name="filter[columns][]" type="checkbox" value="sku"', $rendered);
    }
    
    public function testRenderWithoutSorting()
    {
        $filter = new Columns()->sortable(false);
        
        $filter->apply(
            input: new Input(['columns' => ['id']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku')->indexable(false),
            )),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringNotContainsString('class="crud-drag', $rendered);
    }
    
    public function testRendersCustomView()
    {
        $filter = new Columns()->view('custom/crud/filter');
        
        $filter->apply(
            input: new Input(['columns' => ['id']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
            )),
        );
        
        // empty as view does not exist, but we know that it is changable:
        $this->assertSame('', $filter->render(Factory::createView()));
    }
}