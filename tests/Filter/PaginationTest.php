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
use Tobento\App\Crud\Filter\Pagination;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Test\Factory;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Iterable\ItemFactoryIterator;
use Tobento\Service\Seeder\Str;

class PaginationTest extends TestCase
{
    protected function getController(int $createItems = 0): AbstractCrudController
    {
        $repository = Factory::createStorageRepository(
            table: 'users',
            columns: [
                Column\Id::new(),
                Column\Text::new('sku'),
            ],
        );
        
        if ($createItems > 0) {
            $insertedItems = $repository->storage()->table('users')
                ->chunk(length: 20000)
                ->insertItems(
                    items: new ItemFactoryIterator(
                        function() {
                            return [
                                'sku' => Str::string(10),
                            ];
                        },
                        create: $createItems
                    )
                );
            
            // as generator:
            foreach($insertedItems as $user) {}
        }
        
        return Factory::createCrudController(
            repository: $repository,
            resourceName: 'users',
            fields: [
                //Field\Text::new('id'),
                //Field\Text::new('email'),
            ],
            actions: [
                //Action\Index::new('Users'),
            ],
        );
    }
    
    public function testDefaultInterfaceMethods()
    {
        $filter = Pagination::new();
        
        $this->assertInstanceof(FilterInterface::class, $filter);
        $this->assertSame('pagination_header', $filter->name());
        $this->assertSame('', $filter->fieldName());
        $this->assertSame('header', $filter->getGroup());
        $this->assertSame('footer', $filter->group('footer')->getGroup());
        $this->assertTrue($filter->isOpen());
        $this->assertFalse($filter->open(false)->isOpen());
        $this->assertTrue($filter->open(true)->isOpen());
        $this->assertSame(['pagination' => ['show' => 100, 'page' => 1]], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
        $this->assertSame([], $filter->getOrderByParameters());
        $this->assertSame([0 => 1, 1 => 0], $filter->getLimitParameter());
        $this->assertSame([], $filter->getBeforeCallables());
        $this->assertSame([], $filter->getAfterCallables());
        $this->assertFalse($filter->isActive());
    }
    
    public function testGroupIsAppliedToName()
    {
        $this->assertSame('pagination_header', Pagination::new()->name());
        $this->assertSame('pagination_footer', Pagination::new()->group('footer')->name());
        $this->assertSame('pagination_foo_bar', Pagination::new()->group('foo bar')->name());
    }
    
    public function testApply()
    {
        $filter = Pagination::new();

        $filter->apply(
            input: new Input(['pagination' => ['show' => 100, 'page' => 1]]),
            filters: new Filters(),
            action: Index::new()->setController($this->getController()),
        );
        
        $this->assertSame(['pagination' => ['show' => 100, 'page' => 1]], $filter->getAppliedParameters());
        $this->assertSame([0 => 100, 1 => 0], $filter->getLimitParameter());
        $this->assertSame([], $filter->getWhereParameters());
        $this->assertTrue($filter->isActive());
    }

    public function testApplyShowDefault()
    {
        $filter = Pagination::new(show: 30);
        
        $filter->apply(
            input: new Input(['pagination' => []]),
            filters: new Filters(),
            action: Index::new()->setController($this->getController()),
        );
        
        $this->assertSame(['pagination' => ['show' => 30, 'page' => 1]], $filter->getAppliedParameters());
        $this->assertSame([0 => 30, 1 => 0], $filter->getLimitParameter());
    }
    
    public function testApplyShowIsLimitedByDefault()
    {
        $filter = Pagination::new();

        $filter->apply(
            input: new Input(['pagination' => ['show' => 100000, 'page' => 1]]),
            filters: new Filters(),
            action: Index::new()->setController($this->getController()),
        );
        
        $this->assertSame(['pagination' => ['show' => 1000, 'page' => 1]], $filter->getAppliedParameters());
        $this->assertSame([0 => 1000, 1 => 0], $filter->getLimitParameter());
    }
    
    public function testApplyShowIsLimitedAsDefined()
    {
        $filter = Pagination::new(maxItemsPerPage: 2000);

        $filter->apply(
            input: new Input(['pagination' => ['show' => 100000, 'page' => 1]]),
            filters: new Filters(),
            action: Index::new()->setController($this->getController()),
        );
        
        $this->assertSame(['pagination' => ['show' => 2000, 'page' => 1]], $filter->getAppliedParameters());
        $this->assertSame([0 => 2000, 1 => 0], $filter->getLimitParameter());
    }
    
    public function testApplyWithoutApplyCalledFallsback()
    {
        $filter = Pagination::new();
        
        $this->assertSame(['pagination' => ['show' => 100, 'page' => 1]], $filter->getAppliedParameters());
        // 1 because no count can happen:
        $this->assertSame([0 => 1, 1 => 0], $filter->getLimitParameter());
    } 
    
    public function testApplyWithoutShowParamFallsbackToDefault()
    {
        $filter = Pagination::new();

        $filter->apply(
            input: new Input(['pagination' => ['page' => 1]]),
            filters: new Filters(),
            action: Index::new()->setController($this->getController()),
        );
        
        $this->assertSame(['pagination' => ['show' => 100, 'page' => 1]], $filter->getAppliedParameters());
        $this->assertSame([0 => 100, 1 => 0], $filter->getLimitParameter());
    }

    public function testApplyWithInvalidShowParamFallsbackToDefault()
    {
        $filter = Pagination::new();

        $filter->apply(
            input: new Input(['pagination' => ['show' => [], 'page' => 1]]),
            filters: new Filters(),
            action: Index::new()->setController($this->getController()),
        );
        
        $this->assertSame(['pagination' => ['show' => 100, 'page' => 1]], $filter->getAppliedParameters());
        $this->assertSame([0 => 100, 1 => 0], $filter->getLimitParameter());
    }
    
    public function testApplyWithArrayParamsUsesFirstIfValid()
    {
        $filter = Pagination::new();

        $filter->apply(
            input: new Input(['pagination' => ['show' => [0 => 50], 'page' => [0 => 1]]]),
            filters: new Filters(),
            action: Index::new()->setController($this->getController()),
        );
        
        $this->assertSame(['pagination' => ['show' => 50, 'page' => 1]], $filter->getAppliedParameters());
        $this->assertSame([0 => 50, 1 => 0], $filter->getLimitParameter());
    }
    
    public function testApplyWithNegativeShowParamFallsbackToOne()
    {
        $filter = Pagination::new();

        $filter->apply(
            input: new Input(['pagination' => ['show' => -20, 'page' => 1]]),
            filters: new Filters(),
            action: Index::new()->setController($this->getController()),
        );
        
        $this->assertSame(['pagination' => ['show' => 1, 'page' => 1]], $filter->getAppliedParameters());
        $this->assertSame([0 => 1, 1 => 0], $filter->getLimitParameter());
    }
    
    public function testApplyWithoutPageParamFallsbackToDefault()
    {
        $filter = Pagination::new();

        $filter->apply(
            input: new Input(['pagination' => ['show' => 100]]),
            filters: new Filters(),
            action: Index::new()->setController($this->getController()),
        );
        
        $this->assertSame(['pagination' => ['show' => 100, 'page' => 1]], $filter->getAppliedParameters());
        $this->assertSame([0 => 100, 1 => 0], $filter->getLimitParameter());
    }
    
    public function testApplyWithInvalidPageParamFallsbackToDefault()
    {
        $filter = Pagination::new();

        $filter->apply(
            input: new Input(['pagination' => ['show' => 100, 'page' => []]]),
            filters: new Filters(),
            action: Index::new()->setController($this->getController()),
        );
        
        $this->assertSame(['pagination' => ['show' => 100, 'page' => 1]], $filter->getAppliedParameters());
        $this->assertSame([0 => 100, 1 => 0], $filter->getLimitParameter());
    }
    
    public function testApplyWithoutAnyParamsFallsbackToDefault()
    {
        $filter = Pagination::new();

        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: Index::new()->setController($this->getController()),
        );
        
        $this->assertSame(['pagination' => ['show' => 100, 'page' => 1]], $filter->getAppliedParameters());
        $this->assertSame([0 => 100, 1 => 0], $filter->getLimitParameter());
    }
    
    public function testApplyWithoutInvalidParamsFallsbackToDefault()
    {
        $filter = Pagination::new();

        $filter->apply(
            input: new Input(['pagination' => 'invalid']),
            filters: new Filters(),
            action: Index::new()->setController($this->getController()),
        );
        
        $this->assertSame(['pagination' => ['show' => 100, 'page' => 1]], $filter->getAppliedParameters());
        $this->assertSame([0 => 100, 1 => 0], $filter->getLimitParameter());
    }
    
    public function testApplyPageExists()
    {
        $filter = Pagination::new()->clearTotalItemsCount();

        $filter->apply(
            input: new Input(['pagination' => ['show' => 10, 'page' => 2]]),
            filters: new Filters(),
            action: Index::new()->setController($this->getController(createItems: 25)),
        );
        
        $this->assertSame(['pagination' => ['show' => 10, 'page' => 2]], $filter->getAppliedParameters());
        $this->assertSame([0 => 10, 1 => 10], $filter->getLimitParameter());
    }
    
    public function testApplyPageFallsbackToOneIfNotExists()
    {
        $filter = Pagination::new()->clearTotalItemsCount();

        $filter->apply(
            input: new Input(['pagination' => ['show' => 100, 'page' => 4]]),
            filters: new Filters(),
            action: Index::new()->setController($this->getController()),
        );
        
        $this->assertSame(['pagination' => ['show' => 100, 'page' => 1]], $filter->getAppliedParameters());
        $this->assertSame([0 => 100, 1 => 0], $filter->getLimitParameter());
    }
    
    public function testRender()
    {
        $filter = Pagination::new()->clearTotalItemsCount();
        
        $filter->apply(
            input: new Input(['pagination' => ['show' => 10, 'page' => 2]]),
            filters: new Filters(),
            action: Index::new()->setController($this->getController(createItems: 25)),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<p>of 3 Pages | Showing 11 - 20 from 25 records</p>', $rendered);
        $this->assertStringContainsString('<input id="filter_pagination_page_header" min="1" max="3" aria-label="pagination_header" name="filter[pagination][page]" type="number" value="2">', $rendered);
    }
    
    public function testRenderWithNoRecords()
    {
        $filter = Pagination::new()->clearTotalItemsCount();
        
        $filter->apply(
            input: new Input(['pagination' => ['show' => 10, 'page' => 1]]),
            filters: new Filters(),
            action: Index::new()->setController($this->getController()),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<p>of 1 Pages | Showing 0 - 0 from 0 records</p>', $rendered);
        $this->assertStringContainsString('<input id="filter_pagination_page_header" min="1" max="1" aria-label="pagination_header" name="filter[pagination][page]" type="number" value="1">', $rendered);
    }
    
    public function testRenderGroupIsAppliedToId()
    {
        $filter = Pagination::new()->clearTotalItemsCount();
        
        $filter = Pagination::new()->group('footer')->label('LABEL')->clearTotalItemsCount();
        
        $filter->apply(
            input: new Input(['pagination' => ['show' => 10, 'page' => 2]]),
            filters: new Filters(),
            action: Index::new()->setController($this->getController(createItems: 1)),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input id="filter_pagination_page_footer" min="1" max="1" name="filter[pagination][page]" type="number" value="1">', $rendered);
        $this->assertStringContainsString('label for="filter_pagination_page_footer"', $rendered);
    }
    
    public function testRenderWithCustomLabelAndDesc()
    {
        $filter = Pagination::new()->group('header')->label('LABEL')->description('DESC');
        
        $filter->apply(
            input: new Input(['pagination' => ['show' => 100, 'page' => 1]]),
            filters: new Filters(),
            action: Index::new()->setController($this->getController()),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('LABEL', $rendered);
        $this->assertStringContainsString('DESC', $rendered);
        $this->assertStringContainsString('label for="filter_pagination_page_header"', $rendered);
    }
    
    public function testRenderWithoutLabel()
    {
        $filter = Pagination::new();
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringNotContainsString('label for', $rendered);
    }
    
    public function testRendersCustomView()
    {
        $filter = Pagination::new()->view('custom/crud/filter');
        
        // empty as view does not exist, but we know that it is changable:
        $this->assertSame('', $filter->render(Factory::createView()));
    }
}