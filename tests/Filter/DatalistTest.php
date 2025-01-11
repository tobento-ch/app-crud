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
use Tobento\App\Crud\Filter\Datalist;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Test\Factory;
use Tobento\Service\Repository\Storage\Column;

class DatalistTest extends TestCase
{
    protected function getController(null|array $createItems = null): AbstractCrudController
    {
        $repository = Factory::createStorageRepository(
            table: 'users',
            columns: [
                Column\Id::new(),
                Column\Text::new('sku'),
            ],
        );

        if (is_array($createItems)) {
            $insertedItems = $repository->storage()->table('users')
                ->chunk(length: 20000)
                ->insertItems(items: $createItems);
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
        $filter = Datalist::new(name: 'foo');
        
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
    
    public function testRenderWithoutAnyOptions()
    {
        $filter = Datalist::new(name: 'foo');
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: Index::new()->setController($this->getController()),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertSame('<div data-filter="foo" class="display-none"><datalist id="foo"></datalist></div>', $rendered);
    }
    
    public function testRenderWithOptions()
    {
        $filter = Datalist::new(name: 'foo')->options(['red', 'blue']);
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: Index::new()->setController($this->getController()),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertSame('<div data-filter="foo" class="display-none"><datalist id="foo"><option value="red"></option><option value="blue"></option></datalist></div>', $rendered);
    }
    
    public function testRenderWithOptionsUsingClosure()
    {
        $controller = $this->getController(createItems: [
            ['sku' => 'foo'],
            ['sku' => 'bar'],
        ]);
        
        $filter = Datalist::new(name: 'foo')->options(fn($repo): array => $repo->findAll()->column('sku'));
        
        foreach($filter->getBeforeCallables() as $callable) {
            $callable->resolved($callable->callable()($controller->repository()));
        }
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: Index::new()->setController($controller),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertSame('<div data-filter="foo" class="display-none"><datalist id="foo"><option value="foo"></option><option value="bar"></option></datalist></div>', $rendered);
    }
    
    public function testRenderWithOptionsFromField()
    {
        $controller = $this->getController(createItems: [
            ['sku' => 'foo'],
            ['sku' => 'bar'],
            ['sku' => 'baz'],
        ]);
        
        $filter = Datalist::new(name: 'foo')->optionsFromField(field: 'sku', limit: 2);
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: Index::new()->setController($controller),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertSame('<div data-filter="foo" class="display-none"><datalist id="foo"><option value="foo"></option><option value="bar"></option></datalist></div>', $rendered);
    }
    
    public function testRenderWithOptionsFromFieldIfFieldNotExistsReturnsEmptyDatalist()
    {
        $controller = $this->getController();
        
        $filter = Datalist::new(name: 'foo')->optionsFromField(field: 'unknown', limit: 2);
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: Index::new()->setController($controller),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertSame('<div data-filter="foo" class="display-none"><datalist id="foo"></datalist></div>', $rendered);
    }
    
    public function testRenderWithOptionsFromFieldWithFromInput()
    {
        $controller = $this->getController(createItems: [
            ['sku' => 'foo'],
            ['sku' => 'bar'],
            ['sku' => 'baz'],
            ['sku' => 'bas'],
        ]);
        
        $filter = Datalist::new(name: 'foo')->optionsFromField(field: 'sku', fromInput: 'data', limit: 2);
        
        $filter->apply(
            input: new Input(['data' => 'ba']),
            filters: new Filters(),
            action: Index::new()->setController($controller),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertSame('<div data-filter="foo" class="display-none"><datalist id="foo"><option value="bar"></option><option value="baz"></option></datalist></div>', $rendered);
    }
    
    public function testRenderWithOptionsFromFieldWithFromInputReturnsEmptyDatalistIfInputDataNotExists()
    {
        $controller = $this->getController(createItems: [
            ['sku' => 'foo'],
            ['sku' => 'bar'],
            ['sku' => 'baz'],
            ['sku' => 'bas'],
        ]);
        
        $filter = Datalist::new(name: 'foo')->optionsFromField(field: 'sku', fromInput: 'data', limit: 2);
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: Index::new()->setController($controller),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertSame('<div data-filter="foo" class="display-none"><datalist id="foo"></datalist></div>', $rendered);
    }
    
    public function testRenderWithOptionsFromFieldWithFromInputReturnsEmptyDatalistIfInputDataIsNotString()
    {
        $controller = $this->getController(createItems: [
            ['sku' => 'foo'],
            ['sku' => 'bar'],
            ['sku' => 'baz'],
            ['sku' => 'bas'],
        ]);
        
        $filter = Datalist::new(name: 'foo')->optionsFromField(field: 'sku', fromInput: 'data', limit: 2);
        
        $filter->apply(
            input: new Input(['data' => []]),
            filters: new Filters(),
            action: Index::new()->setController($controller),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertSame('<div data-filter="foo" class="display-none"><datalist id="foo"></datalist></div>', $rendered);
    }
}