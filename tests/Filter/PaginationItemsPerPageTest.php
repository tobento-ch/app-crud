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
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Action\Index;
use Tobento\App\Crud\Filter\PaginationItemsPerPage;
use Tobento\App\Crud\Filter\Pagination;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Test\Factory;

class PaginationItemsPerPageTest extends TestCase
{
    public function testDefaultInterfaceMethods()
    {
        $filter = PaginationItemsPerPage::new();
        
        $this->assertInstanceof(FilterInterface::class, $filter);
        $this->assertSame('pagination_items_header', $filter->name());
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
    
    public function testGroupIsAddedToName()
    {
        $this->assertSame('pagination_items_header', PaginationItemsPerPage::new()->name());
        $this->assertSame('pagination_items_footer', PaginationItemsPerPage::new()->group('footer')->name());
        $this->assertSame('pagination_items_foo_bar', PaginationItemsPerPage::new()->group('foo bar')->name());
    }
    
    public function testApply()
    {
        $filter = PaginationItemsPerPage::new();
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: Index::new(),
        );
        
        $this->assertSame([], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
        $this->assertSame([], $filter->getOrderByParameters());
        $this->assertSame([], $filter->getLimitParameter());
        $this->assertFalse($filter->isActive());
    }

    public function testRenderReturnsEmptyStringIfWithoutPaginationFilter()
    {
        $filter = PaginationItemsPerPage::new();
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: Index::new(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('', $rendered);
    }
    
    public function testRenderShowsDefaultValueIfNotExists()
    {
        $filter = PaginationItemsPerPage::new();
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(
                Pagination::new(),
            ),
            action: Index::new(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input id="filter_pagination_show_header" min="1" max="1000" name="filter[pagination][show]" type="number" value="100">', $rendered);
    }
    
    public function testRenderShowsDefaultValueIfNoneExists()
    {
        $filter = PaginationItemsPerPage::new(show: 75);
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(
                Pagination::new(),
            ),
            action: Index::new(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input id="filter_pagination_show_header" min="1" max="1000" name="filter[pagination][show]" type="number" value="75">', $rendered);
    }
    
    public function testRenderShowsSpecificValueIfExists()
    {
        $filter = PaginationItemsPerPage::new();
        
        $filter->apply(
            input: new Input(['pagination' => ['show' => 50]]),
            filters: new Filters(
                Pagination::new(),
            ),
            action: Index::new(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input id="filter_pagination_show_header" min="1" max="1000" name="filter[pagination][show]" type="number" value="50">', $rendered);
    }
    
    public function testRenderShowsSpecificArrayValueIfExists()
    {
        $filter = PaginationItemsPerPage::new();
        
        $filter->apply(
            input: new Input(['pagination' => ['show' => [50]]]),
            filters: new Filters(
                Pagination::new(),
            ),
            action: Index::new(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input id="filter_pagination_show_header" min="1" max="1000" name="filter[pagination][show]" type="number" value="50">', $rendered);
    }    
    
    public function testRenderShowsDefaultValueIfInvalid()
    {
        $filter = PaginationItemsPerPage::new();
        
        $filter->apply(
            input: new Input(['pagination' => ['show' => []]]),
            filters: new Filters(
                Pagination::new(),
            ),
            action: Index::new(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input id="filter_pagination_show_header" min="1" max="1000" name="filter[pagination][show]" type="number" value="100">', $rendered);
    }
    
    public function testRenderMaxIsSetFromPagination()
    {
        $filter = PaginationItemsPerPage::new();
        
        $filter->apply(
            input: new Input(['pagination' => ['show' => 50]]),
            filters: new Filters(
                Pagination::new(maxItemsPerPage: 500),
            ),
            action: Index::new(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input id="filter_pagination_show_header" min="1" max="500" name="filter[pagination][show]" type="number" value="50">', $rendered);
    }
    
    public function testRenderGroupIsAppliedToId()
    {
        $filter = PaginationItemsPerPage::new()->group('footer')->label('LABEL');
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(
                Pagination::new(),
            ),
            action: Index::new(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input id="filter_pagination_show_footer" min="1" max="1000" name="filter[pagination][show]" type="number" value="100">', $rendered);
        $this->assertStringContainsString('label for="filter_pagination_show_footer"', $rendered);
    }
    
    public function testRenderWithCustomLabelAndDesc()
    {
        $filter = PaginationItemsPerPage::new()->group('header')->label('LABEL')->description('DESC');
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(
                Pagination::new(),
            ),
            action: Index::new(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('LABEL', $rendered);
        $this->assertStringContainsString('DESC', $rendered);
        $this->assertStringContainsString('label for="filter_pagination_show_header"', $rendered);
    }
    
    public function testRenderDefaultLabel()
    {
        $filter = PaginationItemsPerPage::new();
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(
                Pagination::new(),
            ),
            action: Index::new(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<label for="filter_pagination_show_header">Per page</label>', $rendered);
    }
    
    public function testRendersCustomView()
    {
        $filter = PaginationItemsPerPage::new()->view('custom/crud/filter');
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(
                Pagination::new(),
            ),
            action: Index::new(),
        );        
        
        // empty as view does not exist, but we know that it is changable:
        $this->assertSame('', $filter->render(Factory::createView()));
    }
}