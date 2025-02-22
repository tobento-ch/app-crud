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
        $filter = Columns::new();
        
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
        $filter = Columns::new();
        
        $filter->apply(
            input: new Input(['columns' => ['id', 'sku']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'id'),
                Field\Text::new(name: 'sku'),
                Field\Text::new(name: 'title'),
            )),
        );
        
        $this->assertSame(['columns' => ['id', 'sku']], $filter->getAppliedParameters());
        $this->assertSame(['id', 'sku'], $filter->columns());
        $this->assertTrue($filter->isActive());
    }
    
    public function testApplyWithEmptyDataAppliesDefaultFields()
    {
        $filter = Columns::new();
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['columns' => ['sku', 'actions']], $filter->getAppliedParameters());
        $this->assertSame(['sku', 'actions'], $filter->columns());
    }
    
    public function testApplyWithEmptyDataAppliesDefaultFieldsLimits()
    {
        $filter = Columns::new();
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'id'),
                Field\Text::new(name: 'sku'),
                Field\Text::new(name: 'title'),
                Field\Text::new(name: 'desc'),
                Field\Text::new(name: 'intro'),
                Field\Text::new(name: 'created_at'),
                Field\Text::new(name: 'actions'),
            )),
        );
        
        $this->assertSame(6, count($filter->getAppliedParameters()['columns'] ?? []));
        $this->assertSame(6, count($filter->columns()));
    }
    
    public function testApplyWithNoneDataAppliesDefaultFields()
    {
        $filter = Columns::new();
        
        $filter->apply(
            input: new Input(['columns' => ['_none']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['columns' => ['sku', 'actions']], $filter->getAppliedParameters());
        $this->assertSame(['sku', 'actions'], $filter->columns());
    }
    
    public function testApplyWithInvalidDataTypeAppliesDefaultFields()
    {
        $filter = Columns::new();
        
        $filter->apply(
            input: new Input(['columns' => 'invalid']),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['columns' => ['sku', 'actions']], $filter->getAppliedParameters());
        $this->assertSame(['sku', 'actions'], $filter->columns());
    }
    
    public function testApplyIgnoresInvalidFields()
    {
        $filter = Columns::new();
        
        $filter->apply(
            input: new Input(['columns' => ['id', 'invalid', ['invalid']]]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'id'),
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['columns' => ['id']], $filter->getAppliedParameters());
        $this->assertSame(['id'], $filter->columns());
    }
    
    public function testApplyIgnoresNotIndexableFields()
    {
        $filter = Columns::new();
        
        $filter->apply(
            input: new Input(['columns' => ['id', 'sku']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'id'),
                Field\Text::new(name: 'sku')->indexable(false),
            )),
        );
        
        $this->assertSame(['columns' => ['id']], $filter->getAppliedParameters());
        $this->assertSame(['id'], $filter->columns());
    }
    
    public function testRender()
    {
        $filter = Columns::new()->group('header')->label('LABEL')->description('DESC');
        
        $filter->apply(
            input: new Input(['columns' => ['id']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'id'),
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('LABEL', $rendered);
        $this->assertStringContainsString('DESC', $rendered);
        $this->assertStringContainsString('name="filter[columns][]" type="checkbox" value="id"', $rendered);
        $this->assertStringContainsString('name="filter[columns][]" type="checkbox" value="sku"', $rendered);
        $this->assertStringContainsString('name="filter[columns][]" type="hidden" value="_none"', $rendered);
        
        // unique id with group header:
        $this->assertStringContainsString('id="filter_columns_header_1"', $rendered);
        $this->assertStringContainsString('label for="filter_columns_header_1"', $rendered);
    }
    
    public function testRenderIgnoresNotIndexableFields()
    {
        $filter = Columns::new()->group('header')->label('LABEL')->description('DESC');
        
        $filter->apply(
            input: new Input(['columns' => ['id']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'id'),
                Field\Text::new(name: 'sku')->indexable(false),
            )),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringNotContainsString('name="filter[columns][]" type="checkbox" value="sku"', $rendered);
    }
    
    public function testRendersCustomView()
    {
        $filter = Columns::new()->view('custom/crud/filter');
        
        $filter->apply(
            input: new Input(['columns' => ['id']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'id'),
            )),
        );
        
        // empty as view does not exist, but we know that it is changable:
        $this->assertSame('', $filter->render(Factory::createView()));
    }
}