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
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Input\Input;

class FiltersTest extends TestCase
{
    public function testConstructorMethod()
    {
        $filters = new Filters();
        $this->assertInstanceof(FiltersInterface::class, $filters);
        
        $filters = new Filters(new Filter\Pagination());
        $this->assertFalse($filters->empty());
    }
    
    public function testGetAppliedParametersMethod()
    {
        $filters = new Filters(
            new Filter\Input(name: 'foo', field: 'foo'),
            new Filter\Input(name: 'options.color', field: 'options->color'),
            new Filter\Input(name: 'options.meta', field: 'options->meta'),
        );
        
        $this->assertSame([], $filters->getAppliedParameters());

        $action = new Index()->setFields(new Fields(
            new Field\Text(name: 'foo'),
            new Field\Select(name: 'options'),
        ));
                
        $filters->get('foo')->apply(input: new Input(['foo' => 'value']), filters: new Filters(), action: $action);
        $filters->get('options.color')->apply(
            input: new Input(['options' => ['color' => 'red']]),
            filters: new Filters(),
            action: $action
        );
        $filters->get('options.meta')->apply(
            input: new Input(['options' => ['meta' => 'foo']]),
            filters: new Filters(),
            action: $action
        );
        
        $this->assertSame(
            [
                'foo' => 'value',
                'options' => [
                    'color' => 'red',
                    'meta' => 'foo',
                ]
            ],
            $filters->getAppliedParameters()
        );
    }
    
    public function testGetWhereParametersMethod()
    {
        $filters = new Filters(
            new Filter\Input(name: 'foo', field: 'foo')->comparison('>'),
            new Filter\Input(name: 'bar', field: 'foo')->comparison('<'),
            new Filter\Input(name: 'options.color', field: 'options->color'),
        );
        
        $this->assertSame([], $filters->getWhereParameters());

        $action = new Index()->setFields(new Fields(
            new Field\Text(name: 'foo'),
            new Field\Text(name: 'bar'),
            new Field\Select(name: 'options'),
        ));
                
        $filters->get('foo')->apply(input: new Input(['foo' => '5']), filters: new Filters(), action: $action);
        $filters->get('bar')->apply(input: new Input(['bar' => '10']), filters: new Filters(), action: $action);
        $filters->get('options.color')->apply(
            input: new Input(['options' => ['color' => 'red']]),
            filters: new Filters(),
            action: $action
        );
        
        $this->assertSame(
            [
                'foo' => [
                    '>' => '5',
                    '<' => '10',
                ],
                'options->color' => [
                    '=' => 'red',
                ]
            ],
            $filters->getWhereParameters()
        );
    }
    
    public function testGetOrderByParametersMethod()
    {
        $filter = new Filter\FieldsSortOrder();
        
        $filters = new Filters($filter);
        
        $this->assertSame([], $filters->getOrderByParameters());

        $action = new Index()->setFields(new Fields(
            new Field\Text(name: 'foo'),
            new Field\Text(name: 'bar'),
        ));
                
        $filter->apply(input: new Input(['sort' => ['foo' => 'asc', 'bar' => 'desc']]), filters: $filters, action: $action);
        
        $this->assertSame(
            ['foo' => 'asc', 'bar' => 'desc'],
            $filters->getOrderByParameters()
        );
    }
    
    public function testGetLimitParameterMethod()
    {
        $filters = new Filters(
            new Filter\Pagination(),
            new Filter\Pagination()->group('footer'),
        );
        
        $this->assertSame([0 => 1, 1 => 0], $filters->getLimitParameter());
    }
    
    public function testFilterMethod()
    {
        $filters = new Filters(
            new Filter\Input(name: 'foo', field: 'id'),
            new Filter\Input(name: 'bar', field: 'sku'),
        );
        
        $filtered = $filters->filter(fn(FilterInterface $f): bool => $f->name() === 'foo');
        
        $this->assertFalse($filters === $filtered);
        $this->assertSame(2, $filters->count());
        $this->assertSame(1, $filtered->count());
    }
    
    public function testGroupMethod()
    {
        $filters = new Filters(
            new Filter\Input(name: 'foo')->group('header'),
            new Filter\Input(name: 'bar')->group('field'),
        );
        
        $filtersNew = $filters->group(name: 'header');
        
        $this->assertFalse($filters === $filtersNew);
        $this->assertSame(2, $filters->count());
        $this->assertSame(1, $filtersNew->count());
    }
    
    public function testFieldMethod()
    {
        $filters = new Filters(
            new Filter\Input(name: 'foo', field: 'id'),
            new Filter\Input(name: 'bar', field: 'sku'),
        );
        
        $filtersNew = $filters->field(name: 'sku');
        
        $this->assertFalse($filters === $filtersNew);
        $this->assertSame(2, $filters->count());
        $this->assertSame(1, $filtersNew->count());
    }
    
    public function testFieldMethodWithJsonSyntax()
    {
        $filters = new Filters(
            new Filter\Input(name: 'meta.color', field: 'meta->color'),
            new Filter\Input(name: 'meta.roles', field: 'meta->roles'),
        );
        
        $filtersNew = $filters->field(name: 'meta.color');
        
        $this->assertFalse($filters === $filtersNew);
        $this->assertSame(2, $filters->count());
        $this->assertSame(1, $filtersNew->count());
    }
    
    public function testOpenMethod()
    {
        $filters = new Filters(
            new Filter\Input(name: 'foo')->open(true),
            new Filter\Input(name: 'bar')->open(false),
        );
        
        $filtersNew = $filters->open(false);
        
        $this->assertFalse($filters === $filtersNew);
        $this->assertSame(2, $filters->count());
        $this->assertSame(1, $filtersNew->count());
    }
    
    public function testByClassMethod()
    {
        $filters = new Filters(
            new Filter\Input(name: 'foo'),
            new Filter\Pagination(),
        );
        
        $filtersNew = $filters->byClass(name: Filter\Input::class);
        
        $this->assertFalse($filters === $filtersNew);
        $this->assertSame(2, $filters->count());
        $this->assertSame(1, $filtersNew->count());
    }
    
    public function testGetMethod()
    {
        $filters = new Filters();
        $this->assertSame(null, $filters->get(name: 'foo'));
        
        $filters = new Filters(new Filter\Input(name: 'foo'));
        $this->assertSame('foo', $filters->get(name: 'foo')->name());
    }
    
    public function testFirstMethod()
    {
        $filters = new Filters();
        $this->assertSame(null, $filters->first());
        
        $filters = new Filters(new Filter\Input(name: 'foo'), new Filter\Input(name: 'bar'));
        $this->assertSame('foo', $filters->first()->name());
    }
    
    public function testAllMethod()
    {
        $filters = new Filters();
        $this->assertSame([], $filters->all());
        
        $foo = new Filter\Input(name: 'foo');
        $filters = new Filters($foo);
        $this->assertSame(['foo' => $foo], $filters->all());
    }
    
    public function testNamesMethod()
    {
        $filters = new Filters(
            new Filter\Input(name: 'foo'),
            new Filter\Input(name: 'bar'),
        );
        
        $this->assertSame(['foo', 'bar'], $filters->names());
    }
    
    public function testEmptyMethod()
    {
        $filters = new Filters();
        $this->assertTrue($filters->empty());
        
        $filters = new Filters(new Filter\Input(name: 'foo'));
        $this->assertFalse($filters->empty());
    }
    
    public function testCountMethod()
    {
        $filters = new Filters();
        $this->assertSame(0, $filters->count());
        
        $filters = new Filters(new Filter\Input(name: 'foo'));
        $this->assertSame(1, $filters->count());
    }
    
    public function testIteration()
    {
        $filters = new Filters(new Filter\Input(name: 'foo'), new Filter\Input(name: 'bar'));
        
        foreach($filters as $filter) {
            $this->assertInstanceof(FilterInterface::class, $filter);
        }
    }
}