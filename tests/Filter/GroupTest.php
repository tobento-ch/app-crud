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
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Filter\Group;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Test\Factory;

class GroupTest extends TestCase
{
    public function testDefaultInterfaceMethods()
    {
        $filter = new Group(name: 'group');
        
        $this->assertInstanceof(FilterInterface::class, $filter);
        $this->assertSame('group', $filter->name());
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
        $filter = new Group(name: 'group');
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(
                new Filter\Input(name: 'foo')->group('group'),
            ),
            action: new Index(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<div class="filter-group">', $rendered);
        $this->assertStringContainsString('<details open>', $rendered);
        $this->assertStringContainsString('<summary class="link text-s">group</summary>', $rendered);
        $this->assertStringContainsString('name="filter[foo]"', $rendered);
    }
    
    public function testRenderWithLabelAndDesc()
    {
        $filter = new Group(name: 'group')->label('LABEL')->description('DESC');
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(
                new Filter\Input(name: 'foo')->group('group'),
            ),
            action: new Index(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<div class="filter-group">', $rendered);
        $this->assertStringContainsString('<details open>', $rendered);
        $this->assertStringContainsString('<summary class="link text-s">LABEL</summary>', $rendered);
        $this->assertStringContainsString('<p>DESC</p>', $rendered);
        $this->assertStringContainsString('name="filter[foo]"', $rendered);
    }
    
    public function testRenderReturnsEmptyStringWithoutFiltersGrouped()
    {
        $filter = new Group(name: 'group')->label('LABEL')->description('DESC');
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(
                new Filter\Input(name: 'foo')->group('header'),
            ),
            action: new Index(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertSame('', $rendered);
    }
}