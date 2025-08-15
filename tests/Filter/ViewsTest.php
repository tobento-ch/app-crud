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
use Tobento\App\Crud\Action\Index;
use Tobento\App\Crud\Filter\Views;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Test\Factory;

class ViewsTest extends TestCase
{
    public function testDefaultInterfaceMethods()
    {
        $filter = Views::new(name: 'foo');
        
        $this->assertInstanceof(FilterInterface::class, $filter);
        $this->assertSame('foo', $filter->name());
        $this->assertSame('', $filter->fieldName());
        $this->assertSame('header', $filter->getGroup());
        $this->assertSame('footer', $filter->group('footer')->getGroup());
        $this->assertTrue($filter->isOpen());
        $this->assertFalse($filter->open(false)->isOpen());
        $this->assertTrue($filter->open(true)->isOpen());
        $this->assertSame([], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
        $this->assertSame([], $filter->getOrderByParameters());
        $this->assertSame([], $filter->getLimitParameter());
        $this->assertSame([], $filter->getBeforeCallables());
        $this->assertSame([], $filter->getAfterCallables());
        $this->assertFalse($filter->isActive());
    }

    public function testInvalidNameThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $filter = Views::new(name: 'foo bar');
    }

    public function testApplyValue()
    {
        $filter = Views::new()
            ->addView(id: 'default', view: 'crud/index', label: 'Table')
            ->addView(id: 'tree', view: 'crud/index-tree', label: 'Tree');
        
        $filter->apply(
            input: new Input(['views' => 'tree']),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['views' => 'tree', 'views-prev' => 'tree'], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
        $this->assertTrue($filter->isActive());
    }
    
    public function testApplyIgnoresInvalidValue()
    {
        $filter = Views::new()
            ->addView(id: 'default', view: 'crud/index', label: 'Table')
            ->addView(id: 'tree', view: 'crud/index-tree', label: 'Tree');
        
        $filter->apply(
            input: new Input(['views' => 'invalid']),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame([], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
        $this->assertFalse($filter->isActive());
    }    

    public function testApplyWithDefaultView()
    {
        $filter = Views::new()
            ->addView(id: 'default', view: 'crud/index', label: 'Table')
            ->addView(id: 'tree', view: 'crud/index-tree', label: 'Tree')
            ->defaultView(id: 'tree');
        
        $filter->apply(
            input: new Input([]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['views' => 'tree', 'views-prev' => 'tree'], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
        $this->assertTrue($filter->isActive());
    }
    
    public function testApplyWithDefaultViewNotAppliedIfInput()
    {
        $filter = Views::new()
            ->addView(id: 'default', view: 'crud/index', label: 'Table')
            ->addView(id: 'tree', view: 'crud/index-tree', label: 'Tree')
            ->defaultView(id: 'tree');
        
        $filter->apply(
            input: new Input(['views' => 'default']),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['views' => 'default', 'views-prev' => 'default'], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
        $this->assertTrue($filter->isActive());
    }
    
    public function testRender()
    {
        $filter = Views::new()
            ->addView(id: 'default', view: 'crud/index', label: 'Table')
            ->addView(id: 'tree', view: 'crud/index-tree', label: 'Tree')
            ->label('LABEL')
            ->description('DESC');
        
        $filter->apply(
            input: new Input(['views' => 'tree']),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('LABEL', $rendered);
        $this->assertStringContainsString('DESC', $rendered);
        $this->assertStringContainsString('<span class="wrap-v"><input id="filter_views_1" name="filter[views]" type="radio" value="default"><label for="filter_views_1">Table</label></span><span class="wrap-v"><input id="filter_views_2" name="filter[views]" type="radio" value="tree" checked><label for="filter_views_2">Tree</label></span>', $rendered);
    }
    
    public function AtestRendersCustomView()
    {
        $filter = Views::new(name: 'sku', field: 'sku')->view('custom/crud/filter');
        
        // empty as view does not exist, but we know that it is changable:
        $this->assertSame('', $filter->render(Factory::createView()));
    }
}