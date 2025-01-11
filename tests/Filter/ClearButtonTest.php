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
use Tobento\App\Crud\Filter\ClearButton;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Test\Factory;

class ClearButtonTest extends TestCase
{
    public function testDefaultInterfaceMethods()
    {
        $filter = ClearButton::new();
        
        $this->assertInstanceof(FilterInterface::class, $filter);
        $this->assertSame('clear', $filter->name());
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
    
    public function testRender()
    {
        $filter = ClearButton::new();
        
        $rendered = $filter->render(Factory::createView());
        $this->assertSame('<a class="button text-xs" href="?clear-filter=1" data-filter="clear">Clear Filters</a>', $rendered);
    }

    public function testRenderWithSpecificFilters()
    {
        $filter = ClearButton::new(filters: ['foo', 'bar.baz']);
        
        $rendered = $filter->render(Factory::createView());
        $this->assertSame('<a class="button text-xs" href="?clear-filter[]=foo&amp;clear-filter[]=bar.baz" data-filter="clear">Clear Filters</a>', $rendered);
    }
    
    public function testRenderWithSpecificNameAndLabel()
    {
        $filter = ClearButton::new(name: 'foo')->label('Clear');
        
        $rendered = $filter->render(Factory::createView());
        $this->assertSame('<a class="button text-xs" href="?clear-filter=1" data-filter="foo">Clear</a>', $rendered);
    }
    
    public function testRenderWithAttributes()
    {
        $filter = ClearButton::new()->attributes(['data-foo' => 'Foo', 'class' => 'btn']);
        
        $rendered = $filter->render(Factory::createView());
        $this->assertSame('<a data-foo="Foo" class="btn" href="?clear-filter=1" data-filter="clear">Clear Filters</a>', $rendered);
    }
}